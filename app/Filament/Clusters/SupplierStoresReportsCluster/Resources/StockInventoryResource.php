<?php
namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers\DetailsRelationManager;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\Store;
use App\Services\MultiProductsInventoryService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockInventoryResource extends Resource
{
    protected static ?string $model = StockInventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster                             = InventoryManagementCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 9;

    public static function getNavigationLabel(): string
    {
        return 'Stocktakes';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Stocktakes';
    }

    protected static ?string $pluralLabel = 'Stocktake';

    public static function getModelLabel(): string
    {
        return 'Stocktake';
    }
    public static function form(Form $form): Form
    {
        $operaion = $form->getOperation();
        return $form
            ->schema([
                Fieldset::make()->label('')->schema([
                    Grid::make()->columns(4)->schema([
                        DatePicker::make('inventory_date')
                            ->required()->default(now())
                            ->label('Inventory Date')->disabledOn('edit'),
                        Select::make('store_id')->label(__('lang.store'))
                            ->default(getDefaultStore())
                            ->disabledOn('edit')
                            ->live()
                            ->options(
                                Store::active()
                                    ->withManagedStores()
                                    ->get(['name', 'id'])->pluck('name', 'id')
                            )->required()
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $details = $get('details');
                                $storeId = $get('store_id');

                                if (! is_array($details) || ! $storeId) {
                                    return;
                                }

                                $updatedDetails = collect($details)->map(function ($item) use ($storeId) {
                                    $productId = $item['product_id'] ?? null;
                                    $unitId    = $item['unit_id'] ?? null;

                                    if (! $productId || ! $unitId) {
                                        return $item;
                                    }

                                    $service = new \App\Services\MultiProductsInventoryService(
                                        null,
                                        $productId,
                                        $unitId,
                                        $storeId
                                    );

                                    $remainingQty = $service->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0;

                                    $item['system_quantity']   = $remainingQty;
                                    $item['physical_quantity'] = $remainingQty;
                                    $item['difference']        = 0;

                                    return $item;
                                })->toArray();

                                $set('details', $updatedDetails);
                            }),

                        Select::make('responsible_user_id')->searchable()->default(auth()->id())
                            ->relationship('responsibleUser', 'name')->disabledOn('edit')
                            ->required()
                            ->label('Responsible'),
                        $operaion == 'create' ?
                        Select::make('category_id')->visibleOn('create')
                            ->label('Category')
                            ->options(\App\Models\Category::pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                if (! $state) {
                                    return;
                                }

                                $products = \App\Models\Product::where('category_id', $state)
                                    ->where('active', 1)
                                    ->get();

                                $storeId = $get('store_id');

                                $details = $products->map(function ($product) use ($storeId) {
                                    $unitPrice   = $product->unitPrices()->first();
                                    $unitId      = $unitPrice?->unit_id;
                                    $packageSize = $unitPrice?->package_size ?? 0;

                                    $service = new MultiProductsInventoryService(
                                        null,
                                        $product->id,
                                        $unitId,
                                        $storeId
                                    );

                                    $remainingQty = $service->getInventoryForProduct($product->id)[0]['remaining_qty'] ?? 0;

                                    return [
                                        'product_id'        => $product->id,
                                        'unit_id'           => $unitId,
                                        'package_size'      => $packageSize,
                                        'system_quantity'   => $remainingQty,
                                        'physical_quantity' => $remainingQty,
                                        'difference'        => $remainingQty - $remainingQty,
                                    ];
                                })->toArray();

                                $set('details', $details);
                            }) :
                        Toggle::make('edit_enabled')
                            ->label('Edit')
                            ->inline(false)
                            ->default(false)->live()
                            ->helperText('Enable this option to allow editing inventory details')
                            ->dehydrated()
                            ->columnSpan(1),

                    ]),

                    Repeater::make('details')
                    // ->hidden(function ($record) use ($operaion) {
                    //     return $record?->finalized && $operaion === 'edit';
                    // })
                        ->hidden(fn($get, $record) => $operaion === 'edit' && (! $get('edit_enabled') || $record?->finalized))

                        ->collapsible()->collapsed(fn(): bool => $operaion === 'edit')
                        ->relationship('details')
                        ->label('Inventory Details')->columnSpanFull()
                        ->schema([
                            Select::make('product_id')
                                ->required()->columnSpan(2)->distinct()
                                ->label('Product')->searchable()
                                // ->options(function () {
                                //     return Product::where('active', 1)
                                //         ->limit(5)
                                //         ->get(['name', 'id', 'code'])
                                //         ->mapWithKeys(fn($product) => [
                                //             $product->id => "{$product->code} - {$product->name}",
                                //         ]);
                                // })
                                 ->debounce(300)
                                ->getSearchResultsUsing(function (string $search): array {
                                    if (empty($search)) {
                                        // لا تعرض إلا الـ 5 من options
                                        return [];
                                    }
                                    return Product::where('active', 1)
                                        ->where(function ($query) use ($search) {
                                            $query->where('name', 'like', "%{$search}%")
                                                ->orWhere('code', 'like', "%{$search}%");
                                        })
                                        ->limit(15)
                                        ->get()
                                        ->mapWithKeys(fn($product) => [
                                            $product->id => "{$product->code} - {$product->name}",
                                        ])
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)

                                ->reactive()
                                ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                    if (! $state) {
                                        $set('unit_id', null);
                                        return;
                                    }

                                     // بداية القياس
                                     $start = microtime(true);
                                    // استخدم نفس دالة جلب الوحدات كما في unit_id Select
                                    $units = static::getProductUnits($state);

                                    // اختيار أول وحدة في القائمة
                                    $firstUnitId = $units->first()?->unit_id;
 
                                    $set('unit_id', $firstUnitId);
                                       $end = microtime(true);
                                       $duration = round($end - $start, 3); // بالثواني مع ثلاث منازل عشرية
                                       showSuccessNotifiMessage($duration); 
                                    static::handleUnitSelection($set, $get, $firstUnitId);
                                })->placeholder('Select a Product'),

                            Select::make('unit_id')->label('Unit')
                                ->options(function (callable $get) {
                                    $product = \App\Models\Product::find($get('product_id'));
                                    if (! $product) {
                                        return [];
                                    }

                                    // تظهر فقط وحدات supplyOutUnitPrices (كما هو في منطقك الحالي)
                                    return $product->supplyOutUnitPrices
                                        ->pluck('unit.name', 'unit_id')?->toArray() ?? [];
                                })
                            // ->searchable()
                                ->reactive()
                                ->placeholder('Select a Unit')
                                ->extraAttributes(fn($get) => [
                                    'wire:key' => 'unit_id_' . ($get('product_id') ?? 'empty'),
                                ])
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    static::handleUnitSelection($set, $get, $state);
                                })->columnSpan(2)->required(),
                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),

                            TextInput::make('physical_quantity')
                            // ->default(0)
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($set, $state, $get) {

                                    $difference = static::getDifference($get('system_quantity'), $state);
                                    $set('difference', $difference);
                                })->minValue(0)
                                ->label('Physical Qty')
                                ->required(),

                            TextInput::make('system_quantity')->readOnly()
                                ->numeric()
                                ->label('System Qty')
                                ->required(),
                            TextInput::make('difference')->readOnly()
                            // ->rule('not_in:0', 'Now Allowed')

                                ->numeric(),
                        ])->addActionLabel('Add Item')
                        ->columns(8),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->label('ID')->searchable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('inventory_date')->sortable()->label('Date')->toggleable(),
                TextColumn::make('categories_names')->limit(40)
                    ->weight(FontWeight::Medium)->tooltip(fn($record): string => $record->categories_names)
                    ->wrap()->label('Categories')->toggleable(),
                TextColumn::make('details_count')->label('Products No')->alignCenter(true)->toggleable(),
                TextColumn::make('store.name')->sortable()->label('Store')->toggleable(),
                TextColumn::make('responsibleUser.name')->sortable()->label('Responsible')->toggleable(),
                IconColumn::make('finalized')->sortable()->label('Finalized')->boolean()->alignCenter(true)->toggleable(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('inventory_date_range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('inventory_date', '>=', $date))
                            ->when($data['to'], fn($q, $date) => $q->whereDate('inventory_date', '<=', $date));
                    }),
            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Finalize')
                    ->button()
                    ->hidden(fn($record): bool => $record->finalized),
                Tables\Actions\ViewAction::make()
                    ->visible(fn($record): bool => $record->finalized)
                    ->button()
                    ->icon('heroicon-o-eye')->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockInventories::route('/'),
            'create' => Pages\CreateStockInventory::route('/create'),
            'edit'   => Pages\EditStockInventory::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListStockInventories::class,
            Pages\CreateStockInventory::class,
            // Pages\EditStockInventory::class,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query();

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getDifference($remaningQty, $physicalQty)
    {
        $remaningQty = (float) $remaningQty;
        $physicalQty = (float) $physicalQty;
        if ($physicalQty === 0) {
            return 0;
        }
        // dd($remaningQty,$physicalQty);
        $difference = round($physicalQty - $remaningQty, 4);
        return $difference;
    }

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
    public static function getNavigationBadgeColor(): string | array | null
    {
        return Color::Green;
    }

    public static function getProductUnits($productId)
    {
        $product = \App\Models\Product::find($productId);
        if (! $product) {
            return collect();
        }
        return $product->supplyOutUnitPrices ?? collect();
    }

    public static function handleUnitSelection(callable $set, callable $get, $unitId)
    {
        $productId = $get('product_id');
        if (! $productId || ! $unitId) {
            return;
        }

        $unitPrice = \App\Models\UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();

        $service = new \App\Services\MultiProductsInventoryService(
            null,
            $productId,
            $unitId,
            $get('../../store_id'),
        );
        $remaningQty = $service->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0;
        $set('system_quantity', $remaningQty);
        $set('physical_quantity', $remaningQty);
        $difference = static::getDifference($remaningQty, $get('physical_quantity'));
        $set('difference', $difference);
        $set('package_size', $unitPrice->package_size ?? 0);
    }

}