<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitPrice;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
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
                        ->columns(4)
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
                            Toggle::make('active')
                                ->inline(false)->default(true)
                                ->label(__('lang.active')),
                            Textarea::make('description')->label(__('lang.description'))->columnSpanFull()
                                ->rows(2),


                        ]),
                    Step::make('units')
                        ->visible(fn($get): bool => ($get('category_id') !== null && !Category::find($get('category_id'))->is_manafacturing))
                        ->schema([

                            Repeater::make('units')->label(__('lang.units_prices'))
                                ->columns(3)
                                // ->hiddenOn(Pages\EditProduct::class)
                                ->columnSpanFull()
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
                                    TextInput::make('price')->type('number')->default(1)->required()
                                        ->label(__('lang.price'))
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(','),
                                    TextInput::make('package_size')->type('number')->default(1)->required()
                                        ->label(__('lang.package_size'))
                                ])->orderColumn('order')->reorderable()


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
                                        ->searchable(),
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
                                            )->where('unit_id', $state)->first()->price;
                                            $set('price', $unitPrice);

                                            $set('total_price', ((float) $unitPrice) * ((float) $get('quantity')));
                                        }),
                                    TextInput::make('quantity')
                                        ->label(__('lang.quantity'))
                                        ->type('text')
                                        ->default(1)
                                        // ->disabledOn('edit')
                                        // ->mask(
                                        //     fn (TextInput\Mask $mask) => $mask
                                        //         ->numeric()
                                        //         ->decimalPlaces(2)
                                        //         ->thousandsSeparator(',')
                                        // )
                                        ->reactive()
                                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                            $set('total_price', ((float) $state) * ((float)$get('price')));
                                        }),
                                    TextInput::make('price')
                                        ->label(__('lang.price'))
                                        ->type('text')
                                        ->default(1)
                                        ->integer()
                                        // ->disabledOn('edit')
                                        // ->mask(
                                        //     fn (TextInput\Mask $mask) => $mask
                                        //         ->numeric()
                                        //         ->decimalPlaces(2)
                                        //         ->thousandsSeparator(',')
                                        // )
                                        ->reactive()

                                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                            $set('total_price', ((float) $state) * ((float)$get('quantity')));
                                        }),
                                    TextInput::make('total_price')->default(1)
                                        ->type('text')
                                        ->extraInputAttributes(['readonly' => true]),
                                ])
                                ->columns(5) // Adjusts how fields are laid out in each row
                                ->createItemButtonLabel('Add Item') // Custom button label
                                ->minItems(1)

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
                        TextInput::make('price')->type('number')->default(1)
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
                Tables\Columns\TextColumn::make('code')->searchable()
                    ->label(__('lang.code'))
                    ->searchable(isIndividual: true, isGlobal: false),

                Tables\Columns\TextColumn::make('description')->searchable()->label(__('lang.description')),
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
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
                // Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
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
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
