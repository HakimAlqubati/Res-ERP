<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Schemas;

use App\Models\AppLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\MultiProductsInventoryService;
use App\Services\Stock\StockInventory\InventoryProductCacheService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;

class StockInventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $operaion = $schema->getOperation();

        /**
         * تحميل دفعة (batch) من المنتجات إلى details مع تعبئة الكاش للوحدات (EAGER).
         */
        $loadBatch = function (callable $get, callable $set, ?int $forceSize = null): void {
            $pool      = (array) ($get('product_ids_pool') ?? []);
            $loaded    = (int)   ($get('loaded_count') ?? 0);
            $batchSize = (int)   ($forceSize ?? ($get('batch_size') ?? 20));
            $storeId   = (int)   ($get('store_id'));

            if (empty($pool) || ! $storeId) {
                return;
            }

            $slice = array_slice($pool, $loaded, $batchSize);
            if (empty($slice)) {
                Notification::make()->title('No more products')->success()->send();
                return;
            }

            $started  = microtime(true);

            // نجلب الوحدات مع أسمائها دفعة واحدة
            $products = Product::with(['supplyOutUnitPrices.unit'])
                ->whereIn('id', $slice)->get();

            $rows = $products->map(function ($product) use ($storeId) {
                $unitPrices  = $product->supplyOutUnitPrices ?? collect();
                $firstUnit   = $unitPrices->first();
                $firstUnitId = $firstUnit?->unit_id;

                // ✅ EAGER cache لكل الوحدات: package_size + remaining_qty
                $rowUnitsCache = $unitPrices->pluck('unit.name', 'unit_id')->toArray();
                $rowInventoryCache = [];

                foreach ($unitPrices as $u) {
                    $unitId      = $u->unit_id;
                    $packageSize = (float) ($u->package_size ?? 0);

                    // يمكنك لاحقاً استبدال النداء الفردي بنداء Bulk لتحسين الأداء، لكن هذا يطابق سرعة الكود الأول.
                    $service       = new MultiProductsInventoryService(null, $product->id, $unitId, $storeId);
                    $remainingQty  = (float) ($service->getInventoryForProduct($product->id)[0]['remaining_qty'] ?? 0);

                    $rowInventoryCache[$unitId] = [
                        'package_size'  => $packageSize,
                        'remaining_qty' => $remainingQty,
                    ];
                }

                // قيم افتراضية من أول وحدة
                $defaultPackage = (float) ($rowInventoryCache[$firstUnitId]['package_size'] ?? 0);
                $defaultRemain  = (float) ($rowInventoryCache[$firstUnitId]['remaining_qty'] ?? 0);

                return [
                    'product_id'        => $product->id,
                    'unit_id'           => $firstUnitId,
                    'package_size'      => $defaultPackage,
                    'system_quantity'   => $defaultRemain,
                    'physical_quantity' => $defaultRemain,
                    'difference'        => 0,
                    'rowInventoryCache' => $rowInventoryCache,   // ✅ صار جاهز
                    'rowUnitsCache'     => $rowUnitsCache,       // ✅ صار جاهز
                ];
            })->values()->all();

            // دمج مع الموجود
            $current = (array) ($get('details') ?? []);
            $set('details', array_merge($current, $rows));
            $set('loaded_count', $loaded + count($rows));

            $elapsed = round((microtime(true) - $started) * 1000);
            AppLog::write(
                message: 'StockInventory load batch (eager cache)',
                level: AppLog::LEVEL_INFO,
                context: 'StockInventory',
                extra: [
                    'loaded_before' => $loaded,
                    'added_rows'    => count($rows),
                    'loaded_now'    => $loaded + count($rows),
                    'ms'            => $elapsed,
                ]
            );
        };

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

                                        // تحديث الأرصدة لكل صف بناءً على المخزن الجديد مع الحفاظ على الكاش
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

                                            $remainingQty = (float) ($service->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0);

                                            $item['system_quantity']   = $remainingQty;
                                            // لا نلمس physical_quantity إذا كان المستخدم عدّلها سابقًا
                                            $prevPh = (float) ($item['physical_quantity'] ?? $remainingQty);
                                            $userEdited = $prevPh !== (float) ($item['system_quantity'] ?? $remainingQty);
                                            if (! $userEdited) {
                                                $item['physical_quantity'] = $remainingQty;
                                            }
                                            $item['difference'] = (float) (($item['physical_quantity'] ?? 0) - $remainingQty);

                                            // نحدّث الكاش للوحدة الحالية على الأقل
                                            $cache = (array) ($item['rowInventoryCache'] ?? []);
                                            $cache[$unitId] = [
                                                'package_size'  => (float) ($cache[$unitId]['package_size'] ?? ($item['package_size'] ?? 0)),
                                                'remaining_qty' => $remainingQty,
                                            ];
                                            $item['rowInventoryCache'] = $cache;

                                            return $item;
                                        })->toArray();

                                        $set('details', $updatedDetails);
                                    }),

                                Select::make('responsible_user_id')->searchable()->default(auth()->id())
                                    ->relationship('responsibleUser', 'name')->disabledOn('edit')
                                    ->required()
                                    ->label('Responsible'),

                                // 🔽 الحقول المساعدة للتحميل التدريجي
                                Hidden::make('product_ids_pool')->default([])->dehydrated(false),
                                Hidden::make('loaded_count')->default(0)->dehydrated(false),
                                Hidden::make('batch_size')->default(20)->dehydrated(false),

                                $operaion == 'create'
                                    ? Select::make('category_id')->visibleOn('create')
                                        ->label('Category')
                                        ->options(Category::pluck('name', 'id'))
                                        ->reactive()
                                        ->afterStateUpdated(function (callable $set, callable $get, $state) use ($loadBatch) {
                                            try {
                                                if (! $state) {
                                                    return;
                                                }

                                                $started = microtime(true);

                                                // نخزن IDs فقط
                                                $ids = Product::where('category_id', $state)
                                                    ->where('active', 1)
                                                    ->pluck('id')
                                                    ->toArray();

                                                // نُصفّر الحالة ونملأ أول دفعة مع كاش جاهز
                                                $set('product_ids_pool', $ids);
                                                $set('loaded_count', 0);
                                                $set('details', []);

                                                $loadBatch($get, $set, null);

                                                $elapsed = round((microtime(true) - $started) * 1000);
                                                AppLog::write(
                                                    message: 'StockInventory category pool prepared',
                                                    level: AppLog::LEVEL_INFO,
                                                    context: 'StockInventory',
                                                    extra: [
                                                        'category_id' => $state,
                                                        'pool'        => count($ids),
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
                                        })
                                    : Toggle::make('edit_enabled')
                                        ->label('Edit')
                                        ->inline(false)
                                        ->default(false)->reactive()
                                        ->helperText('Enable this option to allow editing inventory details')
                                        ->dehydrated()
                                        ->columnSpan(1),
                            ]),

                        Repeater::make('details')->columnSpanFull()
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
                                        return InventoryProductCacheService::getDefaultOptions()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ])
                                            ->toArray();
                                    })
                                    ->getSearchResultsUsing(function ($search) {
                                        if (empty($search)) return [];
                                        return InventoryProductCacheService::search($search)
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ])
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(
                                        fn($value) => Product::find($value)?->code . ' - ' . Product::find($value)?->name
                                    )
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        if (! $state) {
                                            $set('unit_id', null);
                                            $set('rowInventoryCache', []);
                                            $set('rowUnitsCache', []);
                                            return;
                                        }

                                        // ✅ نفس منطق الأول: املأ الكاش للوحدات فور اختيار المنتج
                                        $product   = Product::with(['supplyOutUnitPrices.unit'])->find($state);
                                        $units     = $product?->supplyOutUnitPrices ?? collect();
                                        $unitsList = $units->pluck('unit.name', 'unit_id')->toArray();
                                        $set('rowUnitsCache', $unitsList);

                                        $storeId = (int) $get('../../store_id');
                                        $cache = [];
                                        foreach ($units as $u) {
                                            $unitId      = $u->unit_id;
                                            $packageSize = (float) ($u->package_size ?? 0);

                                            $service       = new MultiProductsInventoryService(null, $state, $unitId, $storeId);
                                            $remainingQty  = (float) ($service->getInventoryForProduct($state)[0]['remaining_qty'] ?? 0);

                                            $cache[$unitId] = [
                                                'package_size'  => $packageSize,
                                                'remaining_qty' => $remainingQty,
                                            ];
                                        }
                                        $set('rowInventoryCache', $cache);

                                        $firstUnitId = array_key_first($unitsList);
                                        $set('unit_id', $firstUnitId);

                                        // اضبط القيم الافتراضية مباشرة مثل ما نفعل في الباتش
                                        $defaultPackage = (float) ($cache[$firstUnitId]['package_size'] ?? 0);
                                        $defaultRemain  = (float) ($cache[$firstUnitId]['remaining_qty'] ?? 0);
                                        $set('package_size', $defaultPackage);
                                        $set('system_quantity', $defaultRemain);
                                        $set('physical_quantity', $defaultRemain);
                                        $set('difference', 0.0);
                                    })
                                    ->placeholder('Select a Product'),

                                Select::make('unit_id')->label('Unit')
                                    ->options(function (callable $get) {
                                        $product = Product::find($get('product_id'));
                                        if (! $product) return [];
                                        return $product->supplyOutUnitPrices
                                            ->pluck('unit.name', 'unit_id')?->toArray() ?? [];
                                    })
                                    ->reactive()
                                    ->placeholder('Select a Unit')
                                    ->extraAttributes(fn($get) => [
                                        'wire:key' => 'unit_id_' . ($get('product_id') ?? 'empty'),
                                    ])
                                    ->afterStateUpdatedJs(<<<'JS'
                                        (async () => {
                                          let data = ($get('rowInventoryCache') ?? {})[$state];
                                          if (!data) {
                                            // سي rarely يحدث الآن بعد الـ eager cache؛ fallback فقط.
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
                                    ->numeric()
                                    ->reactive()
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
                                    ->numeric(),
                            ])
                            ->addActionLabel('Add Item')
                            ->columns(8),

                        Actions::make([
                            Action::make('load_more_products')
                                ->label('Load more (20)')
                                ->color('primary')
                                ->action(function (callable $get, callable $set) use ($loadBatch) {
                                    $loadBatch($get, $set, null);
                                })
                                ->visible(fn (callable $get) => count((array) $get('product_ids_pool')) > 0),
                            // يمكنك تفعيل زر تحميل الكل إن رغبت:
                            // Action::make('load_all_remaining')
                            //     ->label('Load all remaining')
                            //     ->color('gray')
                            //     ->action(function (callable $get, callable $set) use ($loadBatch) {
                            //         $loadBatch($get, $set, 10000);
                            //     })
                            //     ->visible(fn (callable $get) => count((array) $get('product_ids_pool')) > 0),
                        ])->columnSpanFull(),
                    ]),
            ]);
    }
}
