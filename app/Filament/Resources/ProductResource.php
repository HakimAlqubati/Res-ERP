<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Unit;
use App\Models\UnitPrice;
use App\Services\MigrationScripts\ProductMigrationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action as ActionTable;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $cluster = ProductUnitCluster::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'name';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    // protected static ?string $navigationGroup = 'Products - units';

    public static function getPluralLabel(): ?string
    {
        return __('lang.products');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.products');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return __('lang.products');
    }


    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make()->skippable()
                ->columnSpanFull()
                ->schema([
                    Step::make('')
                        ->columns(5)
                        ->schema([
                            TextInput::make('name')->required()->label(__('lang.name'))
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn($set, $state): string => $set('code', str_replace(' ', '-', $state))),
                            Select::make('category_id')->required()->label(__('lang.category'))
                                ->searchable()->live()
                                ->options(function () {
                                    return Category::pluck('name', 'id');
                                }),
                            TextInput::make('code')->required()->label(__('lang.code')),
                            TextInput::make('minimum_stock_qty')->numeric()->default(0)->required()
                                ->label(__('stock.minimum_quantity'))
                                ->helperText(__('stock.minimum_quantity_desc')),
                            Toggle::make('active')
                                ->inline(false)->default(true)
                                ->label(__('lang.active')),
                            Textarea::make('description')->label(__('lang.description'))->columnSpanFull()
                                ->rows(2),


                        ]),

                    Step::make('products')
                        ->visible(fn($get): bool => ($get('category_id') !== null && Category::find($get('category_id'))->is_manafacturing))
                        ->label('Items')
                        ->schema([
                            Repeater::make('productItems')->relationship('productItems')
                                ->label('Product Items')->schema([
                                    Select::make('product_id')
                                        ->label(__('lang.product'))
                                        ->searchable()
                                        // ->disabledOn('edit')
                                        ->options(function () {
                                            return Product::where('active', 1)
                                                ->unmanufacturingCategory()
                                                ->pluck('name', 'id');
                                        })
                                        ->getSearchResultsUsing(fn(string $search): array => Product::where('active', 1)
                                            ->unmanufacturingCategory()
                                            ->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                        ->getOptionLabelUsing(fn($value): ?string => Product::unmanufacturingCategory()->find($value)?->name)
                                        ->reactive()
                                        ->afterStateUpdated(fn(callable $set) => $set('unit_id', null))
                                        ->searchable()->columnSpan(3),
                                    Select::make('unit_id')
                                        ->label(__('lang.unit'))
                                        // ->disabledOn('edit')
                                        ->options(
                                            function (callable $get) {

                                                $unitPrices = UnitPrice::where('product_id', $get('product_id'))->get()->toArray();

                                                if ($unitPrices)
                                                    return array_column($unitPrices, 'unit_name', 'unit_id');
                                                return [];
                                            }
                                        )
                                        ->searchable()
                                        ->reactive()
                                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                            $unitPrice = UnitPrice::where(
                                                'product_id',
                                                $get('product_id')
                                            )->where('unit_id', $state)->first() ?? null;
                                            $set('price', ($unitPrice->price ?? 0));
                                            $total = ((float) ($unitPrice->price ?? 0)) * ((float) $get('quantity'));
                                            if ($get('qty_waste_percentage') == 0) {
                                                $set('total_price_after_waste', $total);
                                            }
                                            $set('total_price', $total);
                                            // $set('package_size', $unitPrice->package_size ?? 0);
                                            $set('quantity_after_waste', ProductItem::calculateQuantityAfterWaste($get('quantity'), $get('qty_waste_percentage') ?? 0));
                                        })->columnSpan(1),
                                    // TextInput::make('package_size')->numeric()->default(1)->required()
                                    // ->label(__('lang.package_size'))->readOnly(),
                                    TextInput::make('quantity')
                                        ->label(__('lang.quantity'))
                                        ->type('text')
                                        ->default(1)
                                        ->reactive()
                                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                            $res = ((float) $state) * ((float)$get('price'));
                                            if ($get('qty_waste_percentage') == 0) {
                                                $set('total_price_after_waste', $res);
                                            }
                                            $set('total_price', $res);
                                            $set('quantity_after_waste', ProductItem::calculateQuantityAfterWaste($state, $get('qty_waste_percentage') ?? 0));
                                        }),
                                    TextInput::make('price')
                                        ->label(__('lang.price'))
                                        // ->numeric()
                                        ->numeric()
                                        // ->minLength(1)
                                        // ->maxLength(6)
                                        ->default(1)
                                        // ->integer()
                                        // ->disabledOn('edit')
                                        // ->mask(
                                        //     fn (TextInput\Mask $mask) => $mask
                                        //         ->numeric()
                                        //         ->decimalPlaces(2)
                                        //         ->thousandsSeparator(',')
                                        // )
                                        ->reactive()

                                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                            $res = ((float) $state) * ((float)$get('quantity'));
                                            $res = round($res, 1);
                                            if ($get('qty_waste_percentage') == 0) {
                                                $set('total_price_after_waste', $res);
                                            }
                                            $set('total_price', $res);
                                        }),
                                    TextInput::make('total_price')->default(0)
                                        ->type('text')
                                        ->extraInputAttributes(['readonly' => true]),
                                    TextInput::make('qty_waste_percentage')
                                        ->label('Waste %')
                                        ->default(0)
                                        // ->maxLength(2)
                                        // ->minLength(1)
                                        ->maxValue(100)
                                        ->minValue(0)
                                        ->numeric()
                                        // ->suffixIconColor(Color::Green)
                                        // ->suffixIcon('heroicon-o-percent-badge')
                                        ->live()
                                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                            $totalPrice = (float) $get('total_price');

                                            $res = ProductItem::calculateTotalPriceAfterWaste($totalPrice, $state ?? 0);
                                            $res = round($res, 2);
                                            $set('total_price_after_waste', $res);
                                            $set('quantity_after_waste', ProductItem::calculateQuantityAfterWaste($get('quantity'), $state ?? 0));
                                        }),

                                    TextInput::make('total_price_after_waste')->default(0)
                                        ->type('text')->label('Net Price')
                                        ->extraInputAttributes(['readonly' => true]),
                                    Hidden::make('quantity_after_waste'),
                                    // TextInput::make('quantity_after_waste')->default(0)
                                    //     ->type('text')
                                    //     ->extraInputAttributes(['readonly' => true]),
                                ])
                                ->columns(9) // Adjusts how fields are laid out in each row
                                ->createItemButtonLabel('Add Item') // Custom button label
                                ->minItems(1)

                        ]),

                    Step::make('units')->label('Units')
                        ->visible(fn($get): bool => ($get('category_id') !== null && !Category::find($get('category_id'))->is_manafacturing))
                        ->schema([


                            Repeater::make('units')->label(__('lang.units_prices'))
                                ->columns(3)
                                // ->hiddenOn(Pages\EditProduct::class)
                                ->helperText('Note: Please add units in order from smallest to largest.')
                                ->columnSpanFull()->minItems(1)
                                ->collapsible()->defaultItems(0)
                                ->relationship('unitPrices')
                                ->orderable('product_id')
                                ->schema([
                                    Select::make('unit_id')->required()
                                        ->label(__('lang.unit'))
                                        ->searchable()
                                        ->options(function () {
                                            return Unit::pluck('name', 'id');
                                        })->searchable(),
                                    TextInput::make('price')->numeric()->default(1)->required()
                                        ->label(__('lang.price'))
                                        // ->maxLength(6)
                                    // ->mask(RawJs::make('$money($input)'))
                                    // ->stripCharacters(',')
                                    ,
                                    TextInput::make('package_size')->numeric()->default(1)->required()
                                        // ->maxLength(4)
                                        ->label(__('lang.package_size')),

                                ])->orderColumn('order')->reorderable()


                        ]),
                    Step::make('manafacturingProductunits')->label('Units')
                        ->visible(fn($get): bool => ($get('category_id') !== null && Category::find($get('category_id'))->is_manafacturing))
                        ->schema([


                            Repeater::make('units')->label(__('lang.units_prices'))
                                ->columns(3)
                                // ->hiddenOn(Pages\EditProduct::class)
                                ->helperText('Note: Please add units in order from smallest to largest.')
                                ->columnSpanFull()->minItems(1)
                                ->collapsible()->defaultItems(0)
                                ->relationship('unitPrices')
                                ->orderable('product_id')
                                ->schema([
                                    Select::make('unit_id')->required()
                                        ->label(__('lang.unit'))
                                        ->searchable()
                                        ->options(function () {
                                            return Unit::pluck('name', 'id');
                                        })->searchable(),
                                    TextInput::make('package_size')
                                        ->numeric()->default(1)->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($record, $livewire, $set, $state) {
                                            $finalPrice = $livewire->form->getRecord()->final_price ?? 0;
                                            $set('price', $state * $finalPrice);
                                        })
                                        ->label(__('lang.package_size')),
                                    TextInput::make('price')
                                        ->numeric()
                                        ->default(function ($record, $livewire) {
                                            $finalPrice = $livewire->form->getRecord()->final_price ?? 0;
                                            return $finalPrice;
                                        })
                                        ->required()
                                        ->label(__('lang.price'))


                                ])->orderColumn('order')->reorderable()


                        ]),
                ])
        ]);
        return $form

            ->schema([
                TextInput::make('name')->required()->label(__('lang.name')),
                Select::make('category_id')->required()->label(__('lang.category'))
                    ->searchable()
                    ->options(function () {
                        return Category::pluck('name', 'id');
                    }),
                TextInput::make('code')->required()->label(__('lang.code')),

                Textarea::make('description')->label(__('lang.description'))
                    ->rows(2),
                Checkbox::make('active')->label(__('lang.active')),

                Repeater::make('units')->label(__('lang.units_prices'))
                    ->columns(2)
                    ->hiddenOn(Pages\EditProduct::class)
                    ->columnSpanFull()
                    ->collapsible()->defaultItems(0)
                    ->relationship('unitPrices')
                    ->orderable('product_id')
                    ->schema([
                        Select::make('unit_id')
                            ->label(__('lang.unit'))
                            ->searchable()
                            ->options(function () {
                                return Unit::pluck('name', 'id');
                            })->searchable(),
                        TextInput::make('price')->numeric()->default(1)
                            ->label(__('lang.price'))
                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->defaultSort('id', 'desc')
            ->headerActions([
                ActionTable::make('export_employees')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->action(function () {
                        $data = Product::where('active', 1)->select('id', 'name', 'description', 'code')->get();
                        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ProductsExport($data), 'products.xlsx');
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('lang.id'))
                    ->copyable()
                    ->copyMessage(__('lang.product_id_copied'))
                    ->copyMessageDuration(1500)
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('lang.name'))
                    ->toggleable()
                    ->searchable()
                    ->searchable(isIndividual: true)
                    ->tooltip(fn(Model $record): string => "By {$record->name}"),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('lang.name'))
                    ->toggleable()
                    ->searchable()
                    ->searchable(isIndividual: true)
                    ->tooltip(fn(Model $record): string => "By {$record->name}"),
                Tables\Columns\TextColumn::make('code')->searchable()
                    ->label(__('lang.code'))
                    ->searchable(isIndividual: true, isGlobal: false),

                Tables\Columns\TextColumn::make('unit_prices_count')
                    ->label('Unit Prices')->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(true),
                Tables\Columns\TextColumn::make('description')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('lang.description')),
                Tables\Columns\TextColumn::make('category.name')->searchable()->label(__('lang.category'))->alignCenter(true)
                    ->searchable(isIndividual: true, isGlobal: false)->toggleable(),
                Tables\Columns\CheckboxColumn::make('active')->label('Active?')->sortable()->label(__('lang.active'))->toggleable()->alignCenter(true),
                TextColumn::make('final_price')->label('Final Price')->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                Tables\Filters\Filter::make('active')->label(__('lang.active'))
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('active')),
                SelectFilter::make('category_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.category'))->relationship('category', 'name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('updateUnitPrice')
                    ->label('Update Unit Price')->button()->action(function ($record) {
                        $update = ProductMigrationService::updatePackageSizeForProduct($record->id);
                        if ($update) {
                            showSuccessNotifiMessage('Done');
                        } else {
                            showWarningNotifiMessage('Faild');
                        }
                    })->hidden(),

                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
                // Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('updateUnirPricePackageSize')->label('Update Package Unit')->button()
                    ->action(function (Collection $records) {
                        $productIds = $records->pluck('id')->toArray();
                        $allUpdated = true;
                        foreach ($productIds as $productId) {
                            $update = ProductMigrationService::updatePackageSizeForProduct($productId);
                            if (!$update) {
                                $allUpdated = false;
                            }
                        }
                        if ($allUpdated) {
                            showSuccessNotifiMessage('done');
                        } else {
                            showWarningNotifiMessage('faild');
                        }
                    })->hidden(),
                Tables\Actions\BulkAction::make('updateUnirPriceOrder')->label('Update Order Unit')->button()
                    ->action(function (Collection $records) {
                        $productIds = $records->pluck('id')->toArray();

                        foreach ($productIds as $productId) {
                            ProductMigrationService::updateOrderBasedOnPackageSize($productId);
                        }
                    }),
                Tables\Actions\DeleteBulkAction::make(),
                // ExportBulkAction::make(),
                // Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ManageProducts::class,
            Pages\CreateProduct::class,
            Pages\EditProduct::class,
            // Pages\ViewEmployee::class,
        ]);
    }


    public static function getRelations(): array
    {
        return [
            // RelationManagers\UnitPricesRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        // $query->withMinimumUnitPrices();
        return $query;
    }

    /**
     * Recalculate unit prices based on the updated basic price.
     *
     * @param float $basicPrice
     * @param int $mainUnitId
     * @return array
     */
    public static function recalculateUnitPrices(float $basicPrice, int $mainUnitId): array
    {
        $units = Unit::find($mainUnitId)->getParentAndChildrenWithNested();

        return array_map(function ($unit) use ($basicPrice) {
            $operation = $unit['operation'];
            $conversion_factor = $unit['conversion_factor'];

            $price = $basicPrice;
            if ($operation === '*') {
                $price = $basicPrice * $conversion_factor;
            } elseif ($operation === '/') {
                $price = $conversion_factor != 0 ? $basicPrice / $conversion_factor : 0;
            }

            return [
                'unit_id' => $unit['id'],
                'price' => round($price, 2),
            ];
        }, $units);
    }
}
