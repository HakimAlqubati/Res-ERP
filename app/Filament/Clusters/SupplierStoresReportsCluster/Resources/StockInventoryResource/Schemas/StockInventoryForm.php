<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Schemas;

use App\Models\AppLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockInventory;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockInventoryForm
{

    public static function configure(Schema $schema): Schema
    {
        $operaion = $schema->getOperation();

        /**
         * 🔁 تحميل صفحة واحدة فقط إلى page_details (مع استعلام تجميعي فائق السرعة).
         * - يحفظ الصفحة الحالية قبل التنقل.
         * - إذا كانت الصفحة المطلوبة مُحمَّلة سابقاً، يعرضها كما هي بدون إعادة استعلام.
         * - خلاف ذلك يجلب الأرصدة دفعة واحدة باستعلام SQL مجمّع بدلاً من الاستعلام المتكرر.
         */
        $loadPage = function (callable $get, callable $set, int $targetPage, $livewire = null) {
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
                $set('page_selector_bottom', 1);
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
                $set('page_selector_bottom', $targetPage);

                if ($livewire && method_exists($livewire, 'autoSaveDraft')) {
                    $livewire->autoSaveDraft();
                }
                return;
            }

            // ابني الصفحة المطلوبة من الـ pool
            $offset = ($targetPage - 1) * $perPage;
            $slice  = array_slice($pool, $offset, $perPage);
            if (empty($slice)) {
                $set('page_details', []);
                $set('current_page', $targetPage);
                $set('total_pages', $totalPages);
                $set('details_pages', $pagesCache);
                $set('page_selector_bottom', $targetPage);
                return;
            }

            $started  = microtime(true);

            // 🚀 تسريع الجرد: جلب الأرصدة دفعة واحدة باستعلام واحد مجمع فائق السرعة
            $stockBalances = DB::table('inventory_transactions')
                ->whereNull('deleted_at')
                ->where('store_id', $storeId)
                ->whereIn('product_id', $slice)
                ->select('product_id', DB::raw("SUM(CASE WHEN movement_type = 'in' THEN (quantity * package_size) ELSE -(quantity * package_size) END) as rem_base_qty"))
                ->groupBy('product_id')
                ->pluck('rem_base_qty', 'product_id');

            $products = Product::with(['supplyOutUnitPrices.unit'])
                ->whereIn('id', $slice)->get();

            $rows = $products->map(function ($product) use ($storeId, $unitPreference, $stockBalances) {
                $unitPrices  = $product->supplyOutUnitPrices ?? collect();
                // اختيار الوحدة حسب تفضيل المستخدم (الأصغر أو الأكبر)
                $selectedUnit = $unitPreference === 'smallest'
                    ? $unitPrices->sortBy('package_size')->first()
                    : $unitPrices->sortByDesc('package_size')->first();
                $firstUnitId = $selectedUnit?->unit_id;

                $rowUnitsCache     = $unitPrices->pluck('unit.name', 'unit_id')->toArray();
                $rowInventoryCache = [];

                $baseRem = (float) ($stockBalances[$product->id] ?? 0);

                foreach ($unitPrices as $u) {
                    $unitId      = $u->unit_id;
                    $packageSize = (float) ($u->package_size ?? 0);
                    $remainingQty = $packageSize > 0 ? round($baseRem / $packageSize, 4) : 0;

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
            $set('page_selector_bottom', $targetPage);

            if ($livewire && method_exists($livewire, 'autoSaveDraft')) {
                $livewire->autoSaveDraft();
            }

            $elapsed = round((microtime(true) - $started) * 1000);
            AppLog::write(
                message: 'StockInventory load page (batch optimized)',
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
         * ♻️ تحديث أرصدة جميع الصفحات عند تغيير المخزن مع الحفاظ على تعديلات المستخدم (دفعة واحدة).
         */
        $refreshPagesForStore = function (callable $get, callable $set, int $storeId) {
            $pagesCache  = (array) ($get('details_pages') ?? []);
            $current     = (array) ($get('page_details') ?? []);
            $currentPage = (int)   ($get('current_page') ?? 1);

            // احفظ الصفحة الحالية أولاً
            if (! empty($current)) {
                $pagesCache[$currentPage] = $current;
            }

            if (empty($pagesCache)) {
                return;
            }

            // جمع كل معرّفات المنتجات الموجودة بالكاش لاستعلامها دفعة واحدة
            $allProductIds = collect($pagesCache)
                ->flatten(1)
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $stockBalances = empty($allProductIds) ? collect() : DB::table('inventory_transactions')
                ->whereNull('deleted_at')
                ->where('store_id', $storeId)
                ->whereIn('product_id', $allProductIds)
                ->select('product_id', DB::raw("SUM(CASE WHEN movement_type = 'in' THEN (quantity * package_size) ELSE -(quantity * package_size) END) as rem_base_qty"))
                ->groupBy('product_id')
                ->pluck('rem_base_qty', 'product_id');

            foreach ($pagesCache as $pageIdx => $rows) {
                $updated = collect($rows)->map(function ($item) use ($storeId, $stockBalances) {
                    $productId = $item['product_id'] ?? null;
                    $unitId    = $item['unit_id'] ?? null;

                    if (! $productId || ! $unitId) {
                        return $item;
                    }

                    $baseRem = (float) ($stockBalances[$productId] ?? 0);
                    $pkg = (float) ($item['package_size'] ?? 1);
                    $remainingQty = $pkg > 0 ? round($baseRem / $pkg, 4) : 0;

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
                        'package_size'  => $pkg,
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
         * 🔢 مولّد خيارات الصفحات [1 => 'صفحة 1 من X', ...]
         */
        $buildPageOptions = function (callable $get) {
            $pool    = (array) ($get('product_ids_pool') ?? []);
            $perPage = max(1, (int) ($get('per_page') ?? 20));
            $pages   = (int) ceil((count($pool) ?: 0) / $perPage);
            if ($pages < 1) $pages = 1;
            return collect(range(1, $pages))->mapWithKeys(function ($i) use ($pages) {
                return [$i => __('lang.page_x_of_y', ['page' => $i, 'total' => $pages])];
            })->toArray();
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
                                    ->disabled(fn (?\App\Models\StockInventory $record, string $operation) => $operation === 'edit' && (!(isSystemManager() || isSuperAdmin()) && $record?->finalized)),

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
                                Hidden::make('saved_draft_id')->default(null)->dehydrated(true),

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
                                            ->afterStateUpdated(function (callable $set, callable $get, $state, $livewire) use ($loadPage) {
                                                $categoryId = $get('category_id');
                                                if ($categoryId) {
                                                    self::loadCategoryProducts($set, $get, $categoryId, $loadPage, $livewire);
                                                }
                                            }),

                                        Select::make('category_id')
                                            ->label('Category')->searchable()
                                            ->options(Category::active()->notForPos()->pluck('name', 'id'))
                                            ->live(onBlur: false, debounce: 100)
                                            ->afterStateUpdated(function (callable $set, callable $get, $state, $livewire) use ($loadPage) {
                                                if ($state) {
                                                    self::loadCategoryProducts($set, $get, $state, $loadPage, $livewire);
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

                        // ✅ Repeater للعرض فقط (بدون علاقة)، نكتب ناتجه في hidden(details)
                        Repeater::make('page_details')
                            ->statePath('page_details')
                            ->dehydrated(false) // لا يرفع حالته مباشرة
                            ->columnSpanFull()
                            ->collapsible()->visibleOn('create')
                            ->maxItems(30)
                            ->collapsed(fn(): bool => $operaion === 'edit')
                            ->label('Inventory Details')
                            // ✅ عندما يتغير محتوى الصفحة الحالية، خزّنه داخل details_pages[current_page] وحفظ تلقائي
                            ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                                $page  = (int) ($get('../../current_page') ?? 1);
                                $pages = (array) ($get('../../details_pages') ?? []);
                                $pages[$page] = (array) $state;
                                $set('../../details_pages', $pages);

                                if ($livewire && method_exists($livewire, 'autoSaveDraft')) {
                                    $livewire->autoSaveDraft();
                                }
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
                                    ->getOptionLabelUsing(function ($value) {
                                        static $labelsCache = [];
                                        if (!isset($labelsCache[$value])) {
                                            $p = Product::select(['id', 'code', 'name'])->find($value);
                                            $labelsCache[$value] = $p ? "{$p->code} - {$p->name}" : '';
                                        }
                                        return $labelsCache[$value];
                                    })
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
                                        $baseRem = (float) DB::table('inventory_transactions')
                                            ->whereNull('deleted_at')
                                            ->where('store_id', $storeId)
                                            ->where('product_id', $state)
                                            ->select(DB::raw("SUM(CASE WHEN movement_type = 'in' THEN (quantity * package_size) ELSE -(quantity * package_size) END) as rem_qty"))
                                            ->value('rem_qty');

                                        $cache = [];
                                        foreach ($units as $u) {
                                            $unitId      = $u->unit_id;
                                            $packageSize = (float) ($u->package_size ?? 0);
                                            $remainingQty = $packageSize > 0 ? round($baseRem / $packageSize, 4) : 0;

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
                                        $cached = (array) ($get('rowUnitsCache') ?? []);
                                        if (! empty($cached)) {
                                            return $cached;
                                        }
                                        $productId = $get('product_id');
                                        if (! $productId) return [];
                                        $product = Product::with('supplyOutUnitPrices.unit')->find($productId);
                                        return $product?->supplyOutUnitPrices
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
                                    ->extraAttributes(['class' => 'bg-readonly-gray'])
                                    ->extraInputAttributes(['class' => 'text-center cursor-not-allowed', 'readonly' => true]),

                                TextInput::make('physical_quantity')
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-pencil-square')
                                    ->prefixIconColor('danger')
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const sys = Number($get('system_quantity') ?? 0);
                                        const ph  = Number($state ?? 0);
                                        const diff = +(ph - sys).toFixed(4);
                                        $set('difference', diff);
                                    JS)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                                        $page  = (int) ($get('../../current_page') ?? 1);
                                        $pages = (array) ($get('../../details_pages') ?? []);
                                        $currentRows = (array) ($get('../../page_details') ?? []);
                                        if (! empty($currentRows)) {
                                            $pages[$page] = $currentRows;
                                            $set('../../details_pages', $pages);
                                        }

                                        if ($livewire && method_exists($livewire, 'autoSaveDraft')) {
                                            $livewire->autoSaveDraft();
                                        }
                                    })
                                    ->minValue(0)
                                    ->label('Physical Qty')
                                    ->required()
                                    ->extraInputAttributes(['class' => 'text-center']),

                                TextInput::make('system_quantity')->readOnly()
                                    ->numeric()
                                    ->label('System Qty')
                                    ->required()
                                    ->extraAttributes(['class' => 'bg-readonly-gray'])
                                    ->extraInputAttributes(['class' => 'text-center cursor-not-allowed', 'readonly' => true]),

                                TextInput::make('difference')->readOnly()
                                    ->numeric()
                                    ->extraAttributes(['class' => 'bg-readonly-gray'])
                                    ->extraInputAttributes(['class' => 'text-center cursor-not-allowed', 'readonly' => true]),
                            ])
                            ->addActionLabel('Add Item')
                            ->columns(8),

                        // 🧭 شريط تحكم وتصفح الصفحات (أسفل الجدول - ممركز ومتناسق بالكامل)
                        Actions::make([
                            Action::make('prev_page_bottom')
                                ->label(fn () => __('lang.previous'))
                                ->icon(fn () => app()->getLocale() === 'ar' ? 'heroicon-m-chevron-right' : 'heroicon-m-chevron-left')
                                ->color('gray')
                                ->button()
                                ->disabled(fn (callable $get) => (int) ($get('current_page') ?? 1) <= 1)
                                ->action(function (callable $get, callable $set, $livewire) use ($loadPage) {
                                    $curr = (int) ($get('current_page') ?? 1);
                                    $target = max(1, $curr - 1);
                                    $loadPage($get, $set, $target, $livewire);
                                }),

                            Action::make('page_selector_bottom')
                                ->label(fn (callable $get) => __('lang.page_x_of_y', [
                                    'page'  => (int) ($get('current_page') ?? 1),
                                    'total' => max(1, (int) ($get('total_pages') ?? 1)),
                                ]))
                                ->icon('heroicon-m-arrows-up-down')
                                ->color('gray')
                                ->outlined()
                                ->tooltip(fn () => app()->getLocale() === 'ar' ? 'اضغط لاختيار صفحة محددة' : 'Click to jump to a specific page')
                                ->modalHeading(fn () => __('lang.jump_to_page'))
                                ->modalWidth('xs')
                                ->modalSubmitActionLabel(fn () => __('lang.go'))
                                ->form([
                                    Select::make('target_page')
                                        ->label(fn () => __('lang.page'))
                                        ->options(fn (callable $get) => $buildPageOptions($get))
                                        ->default(fn (callable $get) => (int) ($get('current_page') ?? 1))
                                        ->required(),
                                ])
                                ->action(function (array $data, callable $get, callable $set, $livewire) use ($loadPage) {
                                    $page = (int) ($data['target_page'] ?? 1);
                                    $loadPage($get, $set, $page, $livewire);
                                }),

                            Action::make('next_page_bottom')
                                ->label(fn () => __('lang.next'))
                                ->icon(fn () => app()->getLocale() === 'ar' ? 'heroicon-m-chevron-left' : 'heroicon-m-chevron-right')
                                ->iconPosition(\Filament\Support\Enums\IconPosition::After)
                                ->color('primary')
                                ->button()
                                ->disabled(fn (callable $get) => (int) ($get('current_page') ?? 1) >= (int) ($get('total_pages') ?? 1))
                                ->action(function (callable $get, callable $set, $livewire) use ($loadPage) {
                                    $curr = (int) ($get('current_page') ?? 1);
                                    $last = (int) ($get('total_pages') ?? 1);
                                    $target = min($last, $curr + 1);
                                    $loadPage($get, $set, $target, $livewire);
                                }),
                        ])
                        ->alignCenter()
                        ->columnSpanFull()
                        ->visibleOn('create')
                        ->visible(fn (callable $get) => (int) ($get('total_pages') ?? 0) > 1)
                        ->extraAttributes([
                            'style' => 'display: flex !important; justify-content: center !important; align-items: center !important; width: 100% !important; padding-top: 1.25rem; margin-top: 0.5rem; border-top: 1px solid rgba(156, 163, 175, 0.2);',
                        ]),
                    ]),
            ]);
    }

    /**
     * تحميل منتجات الفئة المحددة وتهيئة التقسيم والصفحات
     */
    private static function loadCategoryProducts(callable $set, callable $get, int $categoryId, callable $loadPage, $livewire = null): void
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
            $loadPage($get, $set, 1, $livewire);
            $set('page_selector_bottom', 1);

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
