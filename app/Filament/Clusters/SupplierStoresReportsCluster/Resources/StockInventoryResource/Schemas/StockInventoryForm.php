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
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;

class StockInventoryForm
{

    public static function configure(Schema $schema): Schema
    {
        $operaion = $schema->getOperation();

        /**
         * 🔁 تحميل صفحة واحدة فقط إلى page_details (مع كاش وحدات EAGER).
         * - يحفظ الصفحة الحالية قبل التنقل.
         * - إذا كانت الصفحة المطلوبة مُحمَّلة سابقاً، يعرضها كما هي بدون إعادة بناء.
         * - خلاف ذلك يبني صفوف الصفحة من الـ pool مع ملء الكاش.
         */
        $loadPage = function (callable $get, callable $set, int $targetPage) {
            $pool        = (array) ($get('product_ids_pool') ?? []);
            $perPage     = max(1, (int) ($get('per_page') ?? 20));
            $storeId     = (int) ($get('store_id'));
            $pagesCache  = (array) ($get('details_pages') ?? []);
            $currentPage = (int) ($get('current_page') ?? 1);
            $unitPreference = $get('unit_size_preference') ?? 'largest';

            // لا شيء لعرضه
            if (empty($pool) || ! $storeId) {
                $set('page_details', []);
                $set('current_page', 1);
                $set('total_pages', 0);
                return;
            }

            // احسب عدد الصفحات
            $totalPages = (int) ceil(count($pool) / $perPage);
            $targetPage = min(max(1, $targetPage), max(1, $totalPages));

            // احفظ تعديلات المستخدم في الصفحة الحالية قبل الانتقال
            if ($currentPage > 0) {
                $currentRows = (array) ($get('page_details') ?? []);
                if (! empty($currentRows)) {
                    $pagesCache[$currentPage] = $currentRows;
                } else {
                    unset($pagesCache[$currentPage]); // تأكد من عدم وجود إدخال فارغ
                }
            }

            // إذا الصفحة المطلوبة موجودة بالكاش، استخدمها مباشرة
            if (! empty($pagesCache[$targetPage])) {
                $set('details_pages', $pagesCache);
                $set('page_details', $pagesCache[$targetPage]);
                $set('current_page', $targetPage);
                $set('total_pages', $totalPages);
                return;
            }

            // ابني الصفحة المطلوبة من الـ pool
            $offset = ($targetPage - 1) * $perPage;
            $slice  = array_slice($pool, $offset, $perPage);
            if (empty($slice)) {
                // لا توجد عناصر في هذه الصفحة (قد يحدث إن قلّ حجم الـpool)
                $set('page_details', []);
                $set('current_page', $targetPage);
                $set('total_pages', $totalPages);
                $set('details_pages', $pagesCache);
                return;
            }

            $started  = microtime(true);

            $products = Product::with(['supplyOutUnitPrices.unit'])
                ->whereIn('id', $slice)->get();

            $rows = $products->map(function ($product) use ($storeId, $unitPreference) {
                $unitPrices  = $product->supplyOutUnitPrices ?? collect();
                // اختيار الوحدة حسب تفضيل المستخدم (الأصغر أو الأكبر)
                $selectedUnit = $unitPreference === 'smallest'
                    ? $unitPrices->sortBy('package_size')->first()
                    : $unitPrices->sortByDesc('package_size')->first();
                $firstUnitId = $selectedUnit?->unit_id;

                $rowUnitsCache     = $unitPrices->pluck('unit.name', 'unit_id')->toArray();
                $rowInventoryCache = [];

                foreach ($unitPrices as $u) {
                    $unitId      = $u->unit_id;
                    $packageSize = (float) ($u->package_size ?? 0);

                    $service      = new MultiProductsInventoryService(null, $product->id, $unitId, $storeId);
                    $remainingQty = (float) ($service->getInventoryForProduct($product->id)[0]['remaining_qty'] ?? 0);

                    $rowInventoryCache[$unitId] = [
                        'package_size'  => $packageSize,
                        'remaining_qty' => $remainingQty,
                    ];
                }

                $defaultPackage = (float) ($rowInventoryCache[$firstUnitId]['package_size'] ?? 0);
                $defaultRemain  = (float) ($rowInventoryCache[$firstUnitId]['remaining_qty'] ?? 0);

                return [
                    'product_id'        => $product->id,
                    'unit_id'           => $firstUnitId,
                    'package_size'      => $defaultPackage,
                    'system_quantity'   => $defaultRemain,
                    'physical_quantity' => $defaultRemain,
                    'difference'        => 0,
                    'rowInventoryCache' => $rowInventoryCache,
                    'rowUnitsCache'     => $rowUnitsCache,
                ];
            })->values()->all();

            // خزّن الصفحة وابنِ الحالة
            $pagesCache[$targetPage] = $rows;

            $set('details_pages', $pagesCache);
            $set('page_details', $rows);
            $set('current_page', $targetPage);
            $set('total_pages', $totalPages);

            $elapsed = round((microtime(true) - $started) * 1000);
            AppLog::write(
                message: 'StockInventory load page (eager cache)',
                level: AppLog::LEVEL_INFO,
                context: 'StockInventory',
                extra: [
                    'page'        => $targetPage,
                    'per_page'    => $perPage,
                    'added_rows'  => count($rows),
                    'ms'          => $elapsed,
                ]
            );
        };

        /**
         * ♻️ تحديث أرصدة جميع الصفحات عند تغيير المخزن مع الحفاظ على تعديلات المستخدم.
         */
        $refreshPagesForStore = function (callable $get, callable $set, int $storeId) {
            $pagesCache  = (array) ($get('details_pages') ?? []);
            $current     = (array) ($get('page_details') ?? []);
            $currentPage = (int)   ($get('current_page') ?? 1);

            // احفظ الصفحة الحالية أولاً
            $pagesCache[$currentPage] = $current;

            foreach ($pagesCache as $pageIdx => $rows) {
                $updated = collect($rows)->map(function ($item) use ($storeId) {
                    $productId = $item['product_id'] ?? null;
                    $unitId    = $item['unit_id'] ?? null;

                    if (! $productId || ! $unitId) {
                        return $item;
                    }

                    $service      = new MultiProductsInventoryService(null, $productId, $unitId, $storeId);
                    $remainingQty = (float) ($service->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0);

                    $item['system_quantity'] = $remainingQty;

                    $prevPh     = (float) ($item['physical_quantity'] ?? $remainingQty);
                    $userEdited = $prevPh !== (float) $item['system_quantity'];

                    if (! $userEdited) {
                        $item['physical_quantity'] = $remainingQty;
                    }

                    $item['difference'] = (float) (($item['physical_quantity'] ?? 0) - $remainingQty);

                    // حدّث الكاش للوحدة الحالية
                    $cache = (array) ($item['rowInventoryCache'] ?? []);
                    $cache[$unitId] = [
                        'package_size'  => (float) ($cache[$unitId]['package_size'] ?? ($item['package_size'] ?? 0)),
                        'remaining_qty' => $remainingQty,
                    ];
                    $item['rowInventoryCache'] = $cache;

                    return $item;
                })->toArray();

                $pagesCache[$pageIdx] = $updated;
            }

            // أعِد تحميل الصفحة الحالية من الكاش بعد التحديث
            $set('details_pages', $pagesCache);
            $set('page_details', $pagesCache[$currentPage] ?? []);
        };

        /**
         * 🔢 مولّد خيارات الصفحات [1 => '1', 2 => '2', ...]
         */
        $buildPageOptions = function (callable $get) {
            $pool    = (array) ($get('product_ids_pool') ?? []);
            $perPage = max(1, (int) ($get('per_page') ?? 20));
            $pages   = (int) ceil((count($pool) ?: 0) / $perPage);
            if ($pages < 1) $pages = 1;
            return collect(range(1, $pages))->mapWithKeys(fn($i) => [$i => (string) $i])->toArray();
        };

        return $schema
            ->components([
                Fieldset::make()->label('')
                    ->columnSpanFull()
                    ->schema([
                        // Loading Overlay - يظهر عند اختيار الفئة (داخل الفورم)

                        Grid::make()->columns(3)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('inventory_date')
                                    ->required()->default(now())
                                    ->label('Inventory Date')
                                    ->disabled(fn (?\App\Models\StockInventory $record, string $operation) => $operation === 'edit' && (!(isSystemManager() || isSuperAdmin()) || $record?->finalized)),

                                Select::make('store_id')->label(__('lang.store'))
                                    ->default(getDefaultStore())
                                    ->disabledOn('edit')
                                    ->reactive()->searchable()
                                    ->options(
                                        Store::active()
                                            ->withManagedStores()
                                            ->get(['name', 'id'])->pluck('name', 'id')
                                    )->required()
                                    ->afterStateUpdated(function (callable $get, callable $set) use ($refreshPagesForStore) {
                                        $storeId = (int) $get('store_id');
                                        if (! $storeId) return;
                                        $refreshPagesForStore($get, $set, $storeId);
                                    }),

                                Select::make('responsible_user_id')->searchable()->default(auth()->id())
                                    ->relationship('responsibleUser', 'name')->disabledOn('edit')
                                    ->required()
                                    ->label('Responsible'),

                                // 🆕 مخزن عناصر وتقسيم صفحات
                                Hidden::make('product_ids_pool')->default([])->dehydrated(false),
                                Hidden::make('details_pages')->default([])->dehydrated(false), // [page => rows[]]
                                Hidden::make('current_page')->default(1)->dehydrated(false),
                                Hidden::make('total_pages')->default(0)->dehydrated(false),
                                Hidden::make('per_page')->default(20)->dehydrated(false),

                                // حقل details النهائي (يُرسل للعلاقة عند الحفظ)
                                Hidden::make('_details_payload')
                                    ->dehydrateStateUsing(function (callable $get) {
                                        // جمع كل الصفحات
                                        $page  = (int) ($get('current_page') ?? 1);
                                        $pages = (array) ($get('details_pages') ?? []);
                                        $pages[$page] = (array) ($get('page_details') ?? []);

                                        ksort($pages);

                                        $merged = [];
                                        foreach ($pages as $rows) {
                                            foreach ((array) $rows as $row) {
                                                // نظّف حقول الواجهة قبل الإرسال
                                                unset($row['rowInventoryCache'], $row['rowUnitsCache']);
                                                $merged[] = $row;
                                            }
                                        }
                                        return $merged;
                                    })
                                    ->dehydrated() // مهم
                                    ->visible(false),

                                // اختيار التصنيف يُجهّز المسبح ويحسب الصفحات ويحمّل الصفحة 1
                                $operaion == 'create'
                                    ? Fieldset::make()
                                    ->label('Unit Size Preference (applies to default unit after selecting category)')
                                    ->columnSpanFull()->columns(2)->schema([
                                        ToggleButtons::make('unit_size_preference')
                                            ->label('Default Unit')
                                            ->options([
                                                'largest' => 'Largest',
                                                'smallest' => 'Smallest',
                                            ])
                                            ->icons([
                                                'largest' => 'heroicon-o-arrow-up-circle',
                                                'smallest' => 'heroicon-o-arrow-down-circle',
                                            ])
                                            ->default('smallest')
                                            ->inline()
                                            ->reactive()
                                            ->dehydrated(false)
                                            ->afterStateUpdated(function (callable $set, callable $get, $state) use ($loadPage) {
                                                $categoryId = $get('category_id');
                                                if ($categoryId) {
                                                    self::loadCategoryProducts($set, $get, $categoryId, $loadPage);
                                                }
                                            }),

                                        Select::make('category_id')
                                            ->label('Category')->searchable()
                                            ->options(Category::active()->notForPos()->pluck('name', 'id'))
                                            ->live(onBlur: false, debounce: 100)
                                            ->afterStateUpdated(function (callable $set, callable $get, $state) use ($loadPage) {
                                                if ($state) {
                                                    self::loadCategoryProducts($set, $get, $state, $loadPage);
                                                }
                                            }),
                                        View::make('filament.forms.components.loading-overlay'),

                                    ])
                                    : Toggle::make('edit_enabled')
                                    ->label('Edit')
                                    ->inline(false)->hidden()
                                    ->default(false)->reactive()
                                    ->helperText('Enable this option to allow editing inventory details')
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ]),

                        // 🧭 شريط تحكم الصفحات (قبل الريبيتر)
                        Grid::make()->columns(12)->columnSpanFull()->visibleOn('create')->schema([
                            Select::make('per_page_selector')
                                ->label('Per page')
                                ->hidden()
                                ->options([
                                    10 => '10',
                                    20 => '20',
                                    30 => '30',
                                    // 50 => '50',
                                ])
                                ->default(20)
                                ->dehydrated(false)
                                ->reactive()
                                ->afterStateUpdated(function (callable $get, callable $set, $state) use ($loadPage) {
                                    $state = (int) $state ?: 20;
                                    $set('per_page', $state);

                                    // أعد حساب عدد الصفحات واذهب للصفحة 1
                                    $pool       = (array) ($get('product_ids_pool') ?? []);
                                    $totalPages = (int) ceil((count($pool) ?: 0) / max(1, $state));
                                    $set('total_pages', $totalPages);
                                    $loadPage($get, $set, 1);
                                    $set('page_selector', 1);
                                })
                                ->columnSpan(2),

                            Select::make('page_selector')
                                ->label('Page')
                                ->options(fn(callable $get) => $buildPageOptions($get))
                                ->disabled(fn(callable $get) => (int) ($get('total_pages') ?? 0) <= 1)
                                ->dehydrated(false)
                                ->reactive()
                                ->afterStateUpdated(function (callable $get, callable $set, $state) use ($loadPage) {
                                    $page = (int) $state ?: 1;
                                    $loadPage($get, $set, $page);
                                })
                                ->afterStateHydrated(function (callable $get, callable $set) {
                                    // عند الفتح، اجعل القائمة تشير للصفحة الحالية
                                    $set('page_selector', (int) ($get('current_page') ?? 1));
                                })
                                ->columnSpan(3),

                            Action::make('prev_page')
                                ->label('Prev')
                                ->color('gray')
                                ->action(function (callable $get, callable $set) use ($loadPage) {
                                    $curr = (int) ($get('current_page') ?? 1);
                                    $loadPage($get, $set, max(1, $curr - 1));
                                    $set('page_selector', (int) ($get('current_page') ?? 1));
                                })
                                ->visible(fn(callable $get) => (int) ($get('total_pages') ?? 0) > 1),

                            Action::make('next_page')
                                ->label('Next')
                                ->color('gray')
                                ->action(function (callable $get, callable $set) use ($loadPage) {
                                    $curr = (int) ($get('current_page') ?? 1);
                                    $last = (int) ($get('total_pages') ?? 1);
                                    $loadPage($get, $set, min($last, $curr + 1));
                                    $set('page_selector', (int) ($get('current_page') ?? 1));
                                })
                                ->visible(fn(callable $get) => (int) ($get('total_pages') ?? 0) > 1),
                        ]),

                        // ✅ Repeater للعرض فقط (بدون علاقة)، نكتب ناتجه في hidden(details)
                        Repeater::make('page_details')
                            ->statePath('page_details')
                            ->dehydrated(false) // لا يرفع حالته مباشرة
                            ->columnSpanFull()
                            ->collapsible()->visibleOn('create')
                            ->maxItems(30)
                            ->collapsed(fn(): bool => $operaion === 'edit')
                            ->label('Inventory Details')
                            // ✅ عندما يتغير محتوى الصفحة الحالية، خزّنه داخل details_pages[current_page]
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $page  = (int) ($get('../../current_page') ?? 1);
                                $pages = (array) ($get('../../details_pages') ?? []);
                                $pages[$page] = (array) $state;
                                $set('../../details_pages', $pages);
                            })
                            ->table([

                                TableColumn::make(__('Product'))->width('24rem'),
                                TableColumn::make(__('Unit'))->alignCenter()->width('10rem'),
                                TableColumn::make(__('lang.psize'))->alignCenter()->width('8rem'),
                                TableColumn::make(__('Physical Qty'))->alignCenter()->width('12rem'),
                                TableColumn::make(__('System Qty'))->alignCenter()->width('8rem'),
                                TableColumn::make(__('Difference'))->alignCenter()->width('8rem'),
                            ])
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
                                            // fallback نادر إذا ما كان موجود بالكاش
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
                                    ->label(__('lang.package_size'))
                                    ->extraInputAttributes(['class' => 'text-center']),

                                TextInput::make('physical_quantity')
                                    ->numeric()
                                    ->reactive()
                                    ->prefixIcon('heroicon-o-pencil-square')
                                    ->prefixIconColor('danger')
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const sys = Number($get('system_quantity') ?? 0);
                                        const ph  = Number($state ?? 0);
                                        const diff = +(ph - sys).toFixed(4);
                                        $set('difference', diff);
                                    JS)
                                    ->minValue(0)
                                    ->label('Physical Qty')
                                    ->required()
                                    ->extraInputAttributes(['class' => 'text-center']),

                                TextInput::make('system_quantity')->readOnly()
                                    ->numeric()
                                    ->label('System Qty')
                                    ->required()
                                    ->extraInputAttributes(['class' => 'text-center']),

                                TextInput::make('difference')->readOnly()
                                    ->numeric()
                                    ->extraInputAttributes(['class' => 'text-center']),
                            ])
                            ->addActionLabel('Add Item')
                            ->columns(8),

                        // ⛔️ لا حاجة لأزرار تحميل إضافية — التنقل بالأعلى
                        Actions::make([])->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * تحميل منتجات الفئة المحددة وتهيئة التقسيم والصفحات
     */
    private static function loadCategoryProducts(callable $set, callable $get, int $categoryId, callable $loadPage): void
    {
        try {
            $started = microtime(true);

            $ids = Product::where('category_id', $categoryId)
                ->where('active', 1)
                ->pluck('id')
                ->toArray();

            // صفّر كل شيء وابدأ من الصفحة 1
            $set('product_ids_pool', $ids);
            $set('details_pages', []);
            $set('current_page', 1);
            $set('page_details', []);

            // احسب عدد الصفحات بناءً على per_page الحالي
            $perPage    = max(1, (int) ($get('per_page') ?? 20));
            $totalPages = (int) ceil((count($ids) ?: 0) / $perPage);
            $set('total_pages', $totalPages);

            // حمّل الصفحة الأولى + ثبت المؤشر
            $loadPage($get, $set, 1);
            $set('page_selector', 1);

            $elapsed = round((microtime(true) - $started) * 1000);
            AppLog::write(
                message: 'StockInventory category pool prepared (pagination)',
                level: AppLog::LEVEL_INFO,
                context: 'StockInventory',
                extra: [
                    'category_id' => $categoryId,
                    'pool'        => count($ids),
                    'per_page'    => $perPage,
                    'pages'       => $totalPages,
                    'ms'          => $elapsed,
                ]
            );
        } catch (\Throwable $e) {
            AppLog::write(
                message: $e->getMessage(),
                level: AppLog::LEVEL_ERROR,
                context: 'StockInventory',
                extra: [
                    'category_id' => $categoryId,
                    'trace'       => $e->getTraceAsString(),
                ]
            );
        }
    }
}
