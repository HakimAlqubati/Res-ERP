<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Schemas;

use App\Models\AppLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\MultiProductsInventoryService;
use App\Services\Stock\StockInventory\InventoryProductCacheService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;


class StockInventoryForm
{
    public static function configure(Schema $schema): Schema
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
                                    ->reactive()
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
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {


                                        try {

                                            if (! $state) {
                                                return;
                                            }

                                            $started = microtime(true);
                                            ini_set('memory_limit', '1024M');
                                            $products = Product::where('category_id', $state)
                                                ->where('active', 1)
                                                ->limit(150)
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

                                            // ⚠️ مؤقتًا: قص العدد الظاهر لتخفيف الـDOM أثناء التشخيص
                                            // $details = array_slice($details, 51, 100);

                                            $set('details', $details);

                                            $elapsed = round((microtime(true) - $started) * 1000);
                                            AppLog::write(
                                                message: 'StockInventory category fill',
                                                level: AppLog::LEVEL_INFO,
                                                context: 'StockInventory',
                                                extra: [
                                                    'category_id' => $state,
                                                    'products'    => $products->count(),
                                                    'rows_set'    => count($details),
                                                    'ms'          => $elapsed,
                                                ]
                                            );
                                        } catch (\Throwable $e) {
                                            AppLog::write(
                                                message: $e->getMessage(),
                                                level: AppLog::LEVEL_ERROR,
                                                context: 'StockInventory',
                                                extra: [
                                                    'category_id' => $state,
                                                    'trace'       => $e->getTraceAsString(),
                                                ]
                                            );
                                        }
                                    }) :
                                    Toggle::make('edit_enabled')
                                    ->label('Edit')
                                    ->inline(false)
                                    ->default(false)->reactive()
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
                                    ->reactive()
                                    // ->live(onBlur: true)
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
}
