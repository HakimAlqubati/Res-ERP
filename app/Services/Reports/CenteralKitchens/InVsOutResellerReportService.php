<?php

namespace App\Services\Reports\CenteralKitchens;

use App\Models\ResellerSale;
use App\Models\UnitPrice;
use App\Services\MultiProductsInventoryService;
use Illuminate\Support\Facades\DB;

class InVsOutResellerReportService
{
    private array $smallestUnit = [];


    /**
     * getInData
     *
     * تُرجِع كميات وأسعار إدخالات المخزون (movement_type = 'in') بعد تطبيق الفلاتر،
     * مع توحيد التجميع حسب (المنتج/الوحدة/المتجر/التاريخ) ثم تجمع النتائج وتحوِّلها
     * (عند عدم طلب التفاصيل) إلى مصفوفات موحّدة بالوحدة الأصغر لكل منتج.
     *
     * الفلاتر المدعومة داخل $filters:
     * - product_id:int        تحديد منتج معيّن.
     * - store_id:int          تحديد متجر واحد.
     * - stores:array          قائمة متاجر بصيغة [store_id => name] (يؤخذ المفاتيح).
     * - unit_id:int           تحديد وحدة معينة لسجلات الحركة.
     * - to_date:string        أقصى تاريخ للحركة (YYYY-MM-DD).
     * - details:bool          إن كانت true تُرجع الصفوف الخام بعد GROUP BY بدل التحويل.
     *
     * المخرجات (عند details=false): مصفوفة من العناصر بالشكل:
     * [
     *   'product_id'  => int,
     *   'product_code'=> string,
     *   'product_name'=> string,
     *   'qty'         => float,   // بالوحدة الأصغر
     *   'unit_name'   => string,  // اسم الوحدة الأصغر
     *   'price'       => float,   // تكلفة الوحدة الأصغر (تقريبًا)
     *   'store_name'  => string,
     * ]
     *
     * @param  array $filters
     * @return array
     */
    public function getInData(array $filters = [])
    {

        $query = DB::table('inventory_transactions')
            ->join('products', 'inventory_transactions.product_id', '=', 'products.id')
            ->join('units', 'inventory_transactions.unit_id', '=', 'units.id')
            ->join('stores', 'inventory_transactions.store_id', '=', 'stores.id')

            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.code as product_code',
                'units.name as unit_name',
                'inventory_transactions.package_size',
                DB::raw('SUM(inventory_transactions.quantity) as qty'),
                DB::raw('SUM(inventory_transactions.price) as price'),
                'inventory_transactions.movement_date',
                'inventory_transactions.transaction_date',
                'stores.id as store_id',
                'stores.name as store_name',
            )
            ->whereNull('inventory_transactions.deleted_at')
            ->whereNotIn('inventory_transactions.product_id', [116])

            ->where('inventory_transactions.movement_type', 'in');

        // ✅ تطبيق فلتر واحد فقط (حسب الموجود)
        if (isset($filters['product_id'])) {
            $query->where('inventory_transactions.product_id', $filters['product_id']);
        }
        if (isset($filters['store_id'])) {
            $query->where('inventory_transactions.store_id', $filters['store_id']);
        } elseif (!empty($filters['stores'])) {
            $query->whereIn('inventory_transactions.store_id', array_keys($filters['stores']));
        }

        if (isset($filters['unit_id'])) {
            $query->where('inventory_transactions.unit_id', $filters['unit_id']);
        }





        if (isset($filters['to_date'])) {
            $query->whereDate('inventory_transactions.movement_date', '<=', $filters['to_date']);
        }




        // ✅ التجميع
        $groupBy = [
            'inventory_transactions.product_id',
            'inventory_transactions.unit_id',
            'products.id',
            'products.name',
            'products.code',
            'units.name',
            'inventory_transactions.package_size',
            'inventory_transactions.movement_date',
            'inventory_transactions.transaction_date',
            'inventory_transactions.store_id',
            'stores.id',
            'stores.name',

        ];



        $query->groupBy(...$groupBy);

        $result = $query
            ->get();
        if (isset($filters['details']) && $filters['details'] == true) {
            return $result;
        }
        // $grouped = collect($result)->groupBy('product_id')->toArray();
        $grouped = collect($result)->groupBy(fn($row) => $row->product_id . '-' . $row->store_id)->toArray();

        // dd($grouped);
        $res = $this->transformInResults($grouped);
        // dd($res);
        return $res;
        return $grouped;
    }




    /**
     * transformInResults
     *
     * يحوّل بيانات الإدخال المجمّعة والمجمّوعة حسب (product_id-store_id) إلى كميات وأسعار
     * موحّدة بالاعتماد على أصغر وحدة سعرية مُسجّلة للمنتج في UnitPrice.
     *
     * المُدخل:
     * - $grouped: array  // مصفوفة مجمّعة: key = "productId-storeId", value = قائمة صفوف مُجمَّعة
     *
     * المُخرَج:
     * - مصفوفة عناصر بالشكل:
     *   [
     *     'product_id'   => int,
     *     'product_code' => string,
     *     'product_name' => string,
     *     'qty'          => float,   // بالكرتنة الأصغر/الوحدة الأصغر
     *     'unit_name'    => string,
     *     'price'        => float,   // تكلفة الوحدة الأصغر (تقريبًا)
     *     'store_name'   => string,
     *   ]
     *
     * ملاحظات:
     * - يتم تخزين أصغر وحدة مستخدمة لكل منتج في الخاصية $this->smallestUnit
     *   لإعادة استخدامها لاحقًا في المقارنات.
     *
     * @param  array $grouped
     * @return array
     */
    public function transformInResults(array $grouped)
    {
        $final = [];

        foreach ($grouped as $key => $entries) {
            // key = "productId-storeId"
            [$productId, $storeId] = explode('-', (string) $key);

            $totalQtySmallest = 0.0;
            $totalCostSmallest = 0.0;

            $smallestUnit = $this->getSmallestUnitPrice((int) $productId);
            if (!$smallestUnit) {
                continue;
            }
            $this->smallestUnit[(int) $productId] = $smallestUnit;

            $smallestPackageSize = (float) $smallestUnit->package_size;
            $unitName = $smallestUnit->unit->name ?? '';

            $productName = $entries[0]->product_name ?? '';
            $productCode = $entries[0]->product_code ?? '';
            $storeName   = $entries[0]->store_name ?? '';

            foreach ($entries as $entry) {
                // حوّل الكمية والسعر للوحدة الأصغر
                $multiplier = ((float) $entry->package_size) / $smallestPackageSize;
                $qtySmallest = ((float) $entry->qty) * $multiplier;

                // "price" المجموع في SQL هو مجموع تكلفة العبوات المُسجَّلة لتلك الـ package_size
                // تكلفة الوحدة الأصغر = (إجمالي السعر / package_size) * multiplier
                // لكن الأفضل: حوّل السعر مباشرة للوحدة الأصغر حسب الكمية:
                // إذا كان price هو إجمالي التكلفة لتلك الكمية، فتكلفة الوحدة الأصغر = price / (entry->package_size) * multiplier
                $unitCostAtEntrySize = ((float) $entry->price) / max((float) $entry->package_size, 1);
                $costSmallest = $unitCostAtEntrySize * $multiplier;

                // نجمع "تكلفة للوحدة الأصغر * عدد الوحدات الأصغر"
                // ملاحظة: إن كان price هو مجموع التكاليف للكمية المجمعة، فالأنسب توزيعها على qtySmallest
                $totalQtySmallest  += $qtySmallest;
                $totalCostSmallest += $unitCostAtEntrySize * $qtySmallest / max($multiplier, 1);
            }

            // متوسط تكلفة الوحدة الأصغر (مرجّح بالكمية)
            $pricePerSmallest = $totalQtySmallest > 0 ? ($totalCostSmallest / $totalQtySmallest) : 0;

            $final[] = [
                'product_id'  => (int) $productId,
                'product_code' => $productCode,
                'product_name' => $productName,
                'store_id'    => (int) $storeId,
                'store_name'  => $storeName,
                'qty'         => round($totalQtySmallest, 2),
                'unit_name'   => $unitName,
                'price'       => round($pricePerSmallest, 2),
            ];
        }

        return $final;
    }


    /**
     * getSmallestUnitPrice
     *
     * يجلب أصغر سجل تسعير (UnitPrice) لمنتج معيّن مفلترًا بنطاق "supply/out"
     * (عبر الـ scope: forSupplyAndOut) ومرتبًا تصاعديًا بالحجم package_size.
     *
     * @param  int $productId
     * @return \App\Models\UnitPrice|null  // أصغر وحدة تسعير أو null إن لم توجد
     */
    public function getSmallestUnitPrice($productId)
    {
        return UnitPrice::where('product_id', $productId)->forSupplyAndOut()
            ->orderBy('package_size', 'asc')->first();
    }



    /**
     * getOutData
     *
     * تُرجِع كميات وأسعار إخراجات المخزون (movement_type = 'out') بعد تطبيق الفلاتر،
     * مع التمييز داخل النتائج بين إخراجات تخص مبيعات الموزعين (ResellerSale) وغيرها.
     * تُعاد النتائج إما كسجلات مُجمَّعة خام (عند details=true) أو كمصفوفة موحّدة
     * بالوحدة الأصغر لكل منتج ومتجر.
     *
     * الفلاتر المدعومة داخل $filters:
     * - product_id:int        تحديد منتج معيّن.
     * - store_id:int          تحديد متجر واحد.
     * - stores:array          قائمة متاجر بصيغة [store_id => name] (يؤخذ المفاتيح).
     * - unit_id:int           تحديد وحدة معينة لسجلات الحركة.
     * - from_date:string      بداية نطاق التاريخ (YYYY-MM-DD).
     * - to_date:string        نهاية نطاق التاريخ (YYYY-MM-DD).
     * - details:bool          إن كانت true تُرجع الصفوف الخام بعد GROUP BY.
     *
     * المخرجات (عند details=false): مصفوفة من العناصر بالشكل:
     * [
     *   'product_id'        => int,
     *   'product_code'      => string,
     *   'product_name'      => string,
     *   'store_id'          => int,
     *   'store_name'        => string,
     *   'qty'               => float, // إجمالي الخارج بالوحدة الأصغر
     *   'unit_name'         => string,
     *   'price'             => float, // تكلفة الوحدة الأصغر (تقريبًا)
     *   'out_qty_reseller'  => float, // الخارج لمبيعات الموزعين فقط
     *   'out_qty_other'     => float, // الخارج لغير الموزعين
     * ]
     *
     * @param  array $filters
     * @return array
     */

    public function getOutData(array $filters = [])
    {
        $query = DB::table('inventory_transactions')
            ->join('products', 'inventory_transactions.product_id', '=', 'products.id')
            ->join('units', 'inventory_transactions.unit_id', '=', 'units.id')
            ->join('stores', 'inventory_transactions.store_id', '=', 'stores.id')

            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.code as product_code',
                'units.name as unit_name',
                'inventory_transactions.package_size',
                DB::raw('SUM(inventory_transactions.quantity) as qty'),
                DB::raw('SUM(inventory_transactions.price) as price'),
                'inventory_transactions.movement_date',
                'inventory_transactions.transaction_date',
                'stores.id as store_id',
                'stores.name as store_name',
                'inventory_transactions.transactionable_type',
            )
            ->whereNull('inventory_transactions.deleted_at')
            ->whereNotIn('inventory_transactions.product_id', [116])
            ->where('inventory_transactions.movement_type', 'out');

        // ✅ نفس الفلاتر
        if (isset($filters['product_id'])) {
            $query->where('inventory_transactions.product_id', $filters['product_id']);
        }
        if (isset($filters['store_id'])) {
            $query->where('inventory_transactions.store_id', $filters['store_id']);
        } elseif (!empty($filters['stores'])) {
            $query->whereIn('inventory_transactions.store_id', array_keys($filters['stores']));
        }
        if (isset($filters['unit_id'])) {
            $query->where('inventory_transactions.unit_id', $filters['unit_id']);
        }

        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $query->whereBetween('inventory_transactions.movement_date', [$filters['from_date'], $filters['to_date']]);
        } elseif (isset($filters['from_date'])) {
            $query->whereDate('inventory_transactions.movement_date', '>=', $filters['from_date']);
        } elseif (isset($filters['to_date'])) {
            $query->whereDate('inventory_transactions.movement_date', '<=', $filters['to_date']);
        }


        // if (isset($filters['to_date'])) {
        //     $query->whereDate('inventory_transactions.movement_date', '<=', $filters['to_date']);
        // }

        $groupBy = [
            'inventory_transactions.product_id',
            'inventory_transactions.unit_id',
            'products.id',
            'products.name',
            'products.code',
            'units.name',
            'inventory_transactions.package_size',
            'inventory_transactions.movement_date',
            'inventory_transactions.transaction_date',
            'inventory_transactions.store_id',
            'stores.id',
            'stores.name',
            'inventory_transactions.transactionable_type',

        ];

        $query->groupBy(...$groupBy);

        $result = $query
            ->get();

        if (isset($filters['details']) && $filters['details'] == true) {
            return $result;
        }

        // $grouped = collect($result)->groupBy('product_id')->toArray();
        $grouped = collect($result)->groupBy(fn($row) => $row->product_id . '-' . $row->store_id)->toArray();

        return $this->transformOutResults($grouped);
    }


    /**
     * transformOutResults
     *
     * يحوّل بيانات الإخراج المجمّعة والمجمّوعة حسب (product_id-store_id) إلى كميات وأسعار
     * موحّدة بالاعتماد على أصغر وحدة سعرية مُسجّلة، مع فصل الخارج إلى:
     * - out_qty_reseller: إخراج مرتبط بـ ResellerSale::class
     * - out_qty_other   : باقي أنواع الإخراج
     *
     * المُدخل:
     * - $grouped: array  // مصفوفة مجمّعة: key = "productId-storeId", value = قائمة صفوف مُجمَّعة
     *
     * المُخرَج:
     * - مصفوفة عناصر بالشكل:
     *   [
     *     'product_id'       => int,
     *     'product_code'     => string,
     *     'product_name'     => string,
     *     'store_id'         => int,
     *     'store_name'       => string,
     *     'qty'              => float, // إجمالي الخارج بالوحدة الأصغر
     *     'unit_name'        => string,
     *     'price'            => float, // تكلفة الوحدة الأصغر (تقريبًا)
     *     'out_qty_reseller' => float,
     *     'out_qty_other'    => float,
     *   ]
     *
     * @param  array $grouped
     * @return array
     */

    public function transformOutResults(array $grouped)
    {
        $final = [];

        foreach ($grouped as $key => $entries) {
            [$productId, $storeId] = explode('-', (string) $key);

            $totalQtySmallest = 0.0;
            $totalCostAccum   = 0.0;
            $totalResellerQty = 0.0;
            $totalOtherQty    = 0.0;

            $smallestUnit = $this->getSmallestUnitPrice((int) $productId);
            if (!$smallestUnit) {
                continue;
            }
            $this->smallestUnit[(int) $productId] = $smallestUnit;

            $smallestPackageSize = (float) $smallestUnit->package_size;
            $unitName = $smallestUnit->unit->name ?? '';

            $productName = $entries[0]->product_name ?? '';
            $productCode = $entries[0]->product_code ?? '';
            $storeName   = $entries[0]->store_name ?? '';

            foreach ($entries as $entry) {
                $multiplier   = ((float) $entry->package_size) / $smallestPackageSize;
                $qtySmallest  = ((float) $entry->qty) * $multiplier;

                $unitCostAtEntrySize = ((float) $entry->price) / max((float) $entry->package_size, 1);

                $totalQtySmallest += $qtySmallest;
                $totalCostAccum   += $unitCostAtEntrySize * $qtySmallest / max($multiplier, 1);

                if ($entry->transactionable_type === ResellerSale::class) {
                    $totalResellerQty += $qtySmallest;
                } else {
                    $totalOtherQty    += $qtySmallest;
                }
            }

            $pricePerSmallest = $totalQtySmallest > 0 ? ($totalCostAccum / $totalQtySmallest) : 0;

            $final[] = [
                'product_id'        => (int) $productId,
                'product_code'      => $productCode,
                'product_name'      => $productName,
                'store_id'          => (int) $storeId,
                'store_name'        => $storeName,
                'qty'               => round($totalQtySmallest, 2),
                'unit_name'         => $unitName,
                'price'             => round($pricePerSmallest, 2),
                'out_qty_reseller'  => round($totalResellerQty, 2),
                'out_qty_other'     => round($totalOtherQty, 2),
            ];
        }

        return $final;
    }


    /**
     * getFinalComparison
     *
     * يُدمج نتائج الإدخال (IN) والإخراج (OUT) لكل مفتاح (product_id-store_id)
     * ويُنتج تقرير مقارنة نهائي يتضمن: الكميات الداخلة، الخارج (موزعين/أخرى/إجمالي)،
     * الفروقات، الأسعار التقديرية للوحدة الأصغر، والكمية الحالية من خدمة المخزون.
     *
     * يعتمد على:
     * - getInData($filters)  ثم keyBy("product_id-store_id")
     * - getOutData($filters) ثم keyBy("product_id-store_id")
     * - $this->smallestUnit  أو getSmallestUnitPrice() لتحديد الوحدة الأصغر
     * - MultiProductsInventoryService::getRemainingQty() لجلب الرصيد الحالي
     *
     * الفلاتر نفسها المدعومة في الدوال السابقة (with from/to للـ OUT و to_date للـ IN).
     *
     * المخرجات: مصفوفة عناصر بالشكل:
     * [
     *   'product_id'       => int,
     *   'product_code'     => string,
     *   'product_name'     => string,
     *   'store_id'         => int,
     *   'store_name'       => string|null,
     *   'unit_name'        => string,
     *   'in_qty'           => float,
     *   'out_qty_reseller' => float,
     *   'out_qty_other'    => float,
     *   'out_qty_total'    => float,
     *   'difference'       => float, // in_qty - out_qty_total
     *   'in_price'         => float,
     *   'out_price'        => float,
     *   'current_qty'      => float, // من خدمة المخزون بالوحدة الأصغر
     * ]
     *
     * @param  array $filters
     * @return array
     */

    public function getFinalComparison(array $filters = [])
    {


        $inData  = collect($this->getInData($filters))
            ->keyBy(fn($row) => $row['product_id'] . '-' . $row['store_id']);
        $outData = collect($this->getOutData($filters))
            ->keyBy(fn($row) => $row['product_id'] . '-' . $row['store_id']);

        // لو تحب تفحص عنصر معيّن:
        // dd($outData->get('1056-19'));  // مثال

        $allKeys = $inData->keys()->merge($outData->keys())->unique();

        $finalResult = [];

        foreach ($allKeys as $key) {
            [$pId, $storeId] = explode('-', $key);

            $in  = $inData->get($key);
            $out = $outData->get($key);

            // ملاحظة: الكاش محفوظ على مستوى product_id وليس على مستوى key
            $smallestUnit = $this->smallestUnit[$pId] ?? $this->getSmallestUnitPrice($pId);

            $productName = $in['product_name'] ?? $out['product_name'] ?? '';
            $productCode = $in['product_code'] ?? $out['product_code'] ?? '';
            $unitName    = $in['unit_name'] ?? $out['unit_name'] ?? '';

            $inQty   = $in['qty'] ?? $in['in_qty'] ?? 0; // حسب مخرجاتك الحالية من transformInResults
            // تقسيم الـ out:
            $outQtyReseller = $out['out_qty_reseller'] ?? 0;
            $outQtyOther    = $out['out_qty_other'] ?? 0;
            $outQtyTotal    = $outQtyReseller + $outQtyOther;

            $inPrice  = $in['price'] ?? $in['in_price'] ?? 0;
            // لو كان عندك أسعار مفصولة ممكن تضيفها، لكن حاليًا نخليها كما هي:
            $outPrice = $out['price'] ?? $out['out_price'] ?? 0;

            $currentQty = 0;
            if ($smallestUnit) {
                $currentQty = \App\Services\MultiProductsInventoryService::getRemainingQty(
                    (int) $pId,
                    $smallestUnit->unit_id,
                    (int) $storeId
                );
            }

            $storeName = $in['store_name'] ?? $out['store_name'] ?? null;

            $finalResult[] = [
                'product_id'        => (int) $pId,
                'product_code'      => $productCode,
                'product_name'      => $productName,
                'store_id'          => (int) $storeId,
                'store_name'        => $storeName,
                'unit_name'         => $unitName,

                'in_qty'            => round($inQty, 2),

                // الأعمدة الجديدة
                'out_qty_reseller'  => round($outQtyReseller, 2),
                'out_qty_other'     => round($outQtyOther, 2),
                'out_qty_total'     => round($outQtyTotal, 2),

                'difference'        => round(($inQty) - ($outQtyTotal), 2),

                'in_price'          => round($inPrice, 2),
                'out_price'         => round($outPrice, 2),
                'current_qty'       => round($currentQty, 2),
            ];
        }

        // dd($finalResult);
        return $finalResult;
    }
}
