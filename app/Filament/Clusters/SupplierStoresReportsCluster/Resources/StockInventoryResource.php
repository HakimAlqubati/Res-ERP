<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use App\Models\Category;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\ListStockInventories;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\CreateStockInventory;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\EditStockInventory;
use App\Models\UnitPrice;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers\DetailsRelationManager;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\Store;
use App\Services\MultiProductsInventoryService;
use App\Services\Stock\StockInventory\InventoryProductCacheService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster                             = InventoryManagementCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
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
    public static function form(Schema $schema): Schema
    {
        $operaion = $schema->getOperation();
        return $schema
            ->components([
                Fieldset::make()->label('')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make()->columns(4)
                            ->columnSpanFull()
                            ->schema([
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

                                            $service = new MultiProductsInventoryService(
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
                                    ->options(Category::pluck('name', 'id'))
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        if (! $state) {
                                            return;
                                        }

                                        $products = Product::where('category_id', $state)
                                            ->where('active', 1)
                                            ->get();

                                        $storeId = $get('store_id');

                                        $details = $products->map(function ($product) use ($storeId) {
                                            $unitPrices = $product->supplyOutUnitPrices ?? collect();
                                            $rowCache   = [];
                                        
                                            $firstUnit  = $unitPrices->first();
                                            $firstUnitId = $firstUnit?->unit_id ?? null;
                                        
                                            foreach ($unitPrices as $unitPrice) {
                                                $unitId = $unitPrice->unit_id;
                                                $service = new MultiProductsInventoryService(
                                                    null,
                                                    $product->id,
                                                    $unitId,
                                                    $storeId
                                                );
                                                $remainingQty = $service->getInventoryForProduct($product->id)[0]['remaining_qty'] ?? 0;
                                        
                                                $rowCache[$unitId] = [
                                                    'package_size'  => $unitPrice->package_size ?? 0,
                                                    'remaining_qty' => $remainingQty,
                                                ];
                                            }
                                        
                                            return [
                                                'product_id'        => $product->id,
                                                'unit_id'           => $firstUnitId,
                                                'package_size'      => $rowCache[$firstUnitId]['package_size'] ?? 0,
                                                'system_quantity'   => $rowCache[$firstUnitId]['remaining_qty'] ?? 0,
                                                'physical_quantity' => $rowCache[$firstUnitId]['remaining_qty'] ?? 0,
                                                'difference'        => 0,
                                                'rowInventoryCache' => $rowCache, // ✅ كاش لكل الوحدات
                                                'rowUnitsCache'     => $unitPrices->pluck('unit.name', 'unit_id')->toArray(), // ✅ كاش أسماء الوحدات
                                            ];
                                        })->toArray();
                                        
                                        $set('details', $details);
                                        

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

                        Repeater::make('details')->columnSpanFull()
                            // ->hidden(function ($record) use ($operaion) {
                            //     return $record?->finalized && $operaion === 'edit';
                            // })
                            ->hidden(fn($get, $record) => $operaion === 'edit' && (! $get('edit_enabled') || $record?->finalized))

                            ->collapsible()->collapsed(fn(): bool => $operaion === 'edit')
                            ->relationship('details')
                            ->label('Inventory Details')->columnSpanFull()
                            ->schema([
                                Hidden::make('rowInventoryCache')->default([])->dehydrated(false),
                                Hidden::make('rowUnitsCache')->default([])->dehydrated(false),
                                Select::make('product_id')
                                    ->required()->columnSpan(2)->distinct()
                                    ->label('Product')->searchable()
                                    ->options(function () {
                                        // افتراضيًا أول 5 منتجات
                                        return InventoryProductCacheService::getDefaultOptions()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ])
                                            ->toArray();
                                    })
                                    // ->options(function () {
                                    //     return Product::where('active', 1)
                                    //         ->limit(5)
                                    //         ->get(['name', 'id', 'code'])
                                    //         ->mapWithKeys(fn($product) => [
                                    //             $product->id => "{$product->code} - {$product->name}",
                                    //         ]);
                                    // })
                                    // ->debounce(300)
                                    ->getSearchResultsUsing(function ($search) {
                                        if (empty($search)) {
                                            return [];
                                        }
                                        return InventoryProductCacheService::search($search)
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ])
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(
                                        fn($value) =>
                                        Product::find($value)?->code . ' - ' . Product::find($value)?->name
                                    )
                                    ->reactive()
                                    // ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                    //     if (! $state) {
                                    //         $set('unit_id', null);
                                    //         return;
                                    //     }

                                    //     // استخدم نفس دالة جلب الوحدات كما في unit_id Select
                                    //     $units = static::getProductUnits($state);

                                    //     // اختيار أول وحدة في القائمة
                                    //     $firstUnitId = $units->first()?->unit_id;

                                    //     $set('unit_id', $firstUnitId);
                                    //     static::handleUnitSelection($set, $get, $firstUnitId);
                                    // })
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        if (! $state) {
                                            $set('unit_id', null);
                                            $set('rowInventoryCache', []);
                                            $set('rowUnitsCache', []);
                                            return;
                                        }

                                        // 1) جهّز قائمة وحدات المنتج (supplyOutUnitPrices) وكوّن كاش وحدات
                                        $product   = \App\Models\Product::find($state);
                                        $units     = $product?->supplyOutUnitPrices ?? collect();
                                        $unitsList = $units->pluck('unit.name', 'unit_id')->toArray();
                                        $set('rowUnitsCache', $unitsList);

                                        // 2) حمّل بيانات الكميات المتبقية + package_size للوحدات من السيرفر لمخزن محدد
                                        $storeId = $get('../../store_id');

                                        $cache = [];
                                        foreach ($units as $u) {
                                            $unitId = $u->unit_id;
                                            // يفضّل استعمال كاشك، أو خدمة تجمع الدُفعة Bulk لتقليل الرحلات:
                                            // مثال سريع فردي (استعمل كاشك إن وُجد):
                                            $packageSize = (float) ($u->package_size ?? 0);

                                            $service      = new \App\Services\MultiProductsInventoryService(null, $state, $unitId, $storeId);
                                            $remainingQty = (float) ($service->getInventoryForProduct($state)[0]['remaining_qty'] ?? 0);

                                            $cache[$unitId] = [
                                                'package_size'  => $packageSize,
                                                'remaining_qty' => $remainingQty,
                                            ];
                                        }
                                        $set('rowInventoryCache', $cache);

                                        // 3) اضبط أول وحدة افتراضيًا
                                        $firstUnitId = array_key_first($unitsList);
                                        $set('unit_id', $firstUnitId);
                                        // لا حاجة لاستدعاء handleUnitSelection هنا، سنترك الحساب للـ JS عندما يتغيّر unit_id.
                                    })
                                    ->placeholder('Select a Product'),

                                Select::make('unit_id')->label('Unit')
                                    ->options(function (callable $get) {
                                        $product = Product::find($get('product_id'));
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
                                    // ->afterStateUpdated(function (Set $set, $state, $get) {
                                    //     static::handleUnitSelection($set, $get, $state);
                                    // })
                                    ->afterStateUpdatedJs(<<<'JS'
                                    (async () => {
                                      let data = ($get('rowInventoryCache') ?? {})[$state];
                                      if (!data) {
                                        const productId = $get('product_id');
                                        const storeId   = $get('../../store_id');
                                        data = await $wire.getInventoryRowData(productId, $state, storeId);
                                        const cache = $get('rowInventoryCache') ?? {};
                                        cache[$state] = data ?? { package_size: 0, remaining_qty: 0 };
                                        $set('rowInventoryCache', cache);
                                      }
                                    
                                      const pkg = Number(data?.package_size ?? 0);
                                      const rem = Number(data?.remaining_qty ?? 0);
                                    
                                      const prevSys = Number($get('system_quantity'));
                                      const prevPh  = Number($get('physical_quantity'));
                                      const userEdited = !Number.isNaN(prevPh) && prevPh !== prevSys;
                                    
                                      $set('package_size', pkg);
                                      $set('system_quantity', rem);
                                    
                                      // لا تلمس physical إذا كان المستخدم عدّلها سابقًا
                                      if (!userEdited) {
                                        $set('physical_quantity', rem);
                                      }
                                    
                                      const ph = Number($get('physical_quantity') ?? rem);
                                      $set('difference', +(ph - rem).toFixed(4));
                                    })();
                                    JS)
                                    

                                    ->columnSpan(2)->required(),
                                TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                    ->label(__('lang.package_size')),

                                TextInput::make('physical_quantity')
                                    // ->default(0)
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->afterStateUpdatedJs(<<<'JS'
                                    const sys = Number($get('system_quantity') ?? 0);
                                    const ph  = Number($state ?? 0);
                                    const diff = +(ph - sys).toFixed(4);
                                    $set('difference', diff);
                                JS)
                                    ->minValue(0)
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        // InventoryProductCacheService::clearAllCache();
        // InventoryProductCacheService::cacheAllActiveProducts();
        // InventoryProductCacheService::cacheInventoryForAllActiveStores();

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
                TrashedFilter::make(),
                Filter::make('inventory_date_range')
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('inventory_date', '>=', $date))
                            ->when($data['to'], fn($q, $date) => $q->whereDate('inventory_date', '<=', $date));
                    }),
            ], FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make()
                    ->label('Finalize')
                    ->button()
                    ->hidden(fn($record): bool => $record->finalized),
                ViewAction::make()
                    ->visible(fn($record): bool => $record->finalized)
                    ->button()
                    ->icon('heroicon-o-eye')->color('success'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
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
            'index'  => ListStockInventories::route('/'),
            // 'new-create' => StockInventoryReactPage::route('/new-create'),
            'create' => CreateStockInventory::route('/create'),
            'edit'   => EditStockInventory::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListStockInventories::class,
            CreateStockInventory::class,
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
        $product = Product::find($productId);
        if (! $product) {
            return collect();
        }
        return $product->supplyOutUnitPrices ?? collect();
    }

    public static function handleUnitSelection(callable $set, callable $get, $unitId)
    {
        $productId = $get('product_id');
        $start     = microtime(true);
        if (! $productId || ! $unitId) {
            return;
        }

        $unitPrice = UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();

        $service = new MultiProductsInventoryService(
            null,
            $productId,
            $unitId,
            $get('../../store_id'),
        );
        // $inventoryFromCache = InventoryProductCacheService::getCachedInventoryForProduct($get('product_id'), $unitId, $get('../../store_id'));

        // $packageSize = $inventoryFromCache['package_size'] ?? 0;
        // $remaningQty = $inventoryFromCache['remaining_qty'] ?? 0;
        $packageSize = $unitPrice->package_size ?? 0;
        $remaningQty = $service->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0;

        $set('system_quantity', $remaningQty);
        $set('physical_quantity', $remaningQty);
        $difference = static::getDifference($remaningQty, $get('physical_quantity'));
        $set('difference', $difference);
        $set('package_size', $packageSize);
        // Stop timing and calculate duration
        $end          = microtime(true);
        $duration     = $end - $start;
        $seconds      = floor($duration);
        $milliseconds = round(($duration - $seconds) * 1000, 2);
        // showSuccessNotifiMessage('( '. $seconds.'Seconds ) ('. $milliseconds .' Milliseconds)');

    }
}
