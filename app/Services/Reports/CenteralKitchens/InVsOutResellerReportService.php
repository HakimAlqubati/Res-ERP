<?php

namespace App\Services\Reports\CenteralKitchens;

use App\Models\ResellerSale;
use App\Models\UnitPrice;
use App\Services\MultiProductsInventoryService;
use Illuminate\Support\Facades\DB;

class InVsOutResellerReportService
{
    private array $smallestUnit = [];
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

    public function transformInResults(array $grouped)
    {
        $final = [];

        // dd($grouped);
        foreach ($grouped as $productId => $entries) {
            $totalQty = 0;
            $totalCost = 0;

            $storeName = null;
            // الحصول على الوحدة الصغيرة وكود العبوة الخاصة بها
            $smallestUnit = $this->getSmallestUnitPrice($productId);
            if (!$smallestUnit) {
                continue; // تجاهل المنتج لو ما عنده وحدة معرفة
            }
            $this->smallestUnit[$productId] = $smallestUnit;

            $smallestPackageSize = $smallestUnit->package_size;
            $unitName = $smallestUnit->unit->name;


            $productName = $entries[0]->product_name; // يفترض الاسم ثابت
            $productCode = $entries[0]->product_code; // يفترض الاسم ثابت

            // نحول كل الكميات للوحدة الصغيرة
            foreach ($entries as $entry) {

                $multiplier = $entry->package_size / $smallestPackageSize;
                $convertedQty = $entry->qty * $multiplier;
                $totalQty += $convertedQty;
                $totalCost = $entry->price / $entry->package_size;
                $storeName = $entry->store_name;
                // dd($entry);
            }


            $final[] = [
                'product_id'   => $productId,
                'product_code'   => $productCode,
                'product_name' => $productName,
                'product_name' => $productName,
                'qty'          => round($totalQty, 2),
                'unit_name'    => $unitName,
                'price' => round($totalCost, 2),
                'store_name' => $storeName,
            ];
        }

        return $final;
    }
    public function getSmallestUnitPrice($productId)
    {
        return UnitPrice::where('product_id', $productId)->forSupplyAndOut()
            ->orderBy('package_size', 'asc')->first();
    }

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
    public function transformOutResults(array $grouped)
    {
        $final = [];

        foreach ($grouped as $productId => $entries) {
            $totalQty = 0;
            $totalCost = 0;

            $totalResellerQty = 0;
            $totalOtherQty = 0;


            $smallestUnit = $this->getSmallestUnitPrice($productId);
            $this->smallestUnit[$productId] = $smallestUnit;
            if (!$smallestUnit) {
                continue;
            }

            $smallestPackageSize = $smallestUnit->package_size;
            $unitName = $smallestUnit->unit->name;

            $productName = $entries[0]->product_name;
            $productCode = $entries[0]->product_code;

            $storeName = null;
            foreach ($entries as $entry) {
                $multiplier = $entry->package_size / $smallestPackageSize;
                $convertedQty = $entry->qty * $multiplier;
                $totalQty += $convertedQty;
                $totalCost = $entry->price / $entry->package_size;
                $storeName = $entry->store_name;

                if ($entry->transactionable_type === ResellerSale::class) {
                    $totalResellerQty += $convertedQty;
                } else {
                    $totalOtherQty += $convertedQty;
                }
            }

            $storeId   = $entries[0]->store_id;
            $storeName = $entries[0]->store_name;
            $final[] = [
                'product_id'   => $productId,
                'product_code' => $productCode,
                'product_name' => $productName,
                'store_id'     => $storeId,
                'store_name'   => $storeName,
                'qty'          => round($totalQty, 2),
                'unit_name'    => $unitName,
                'price'        => round($totalCost, 2),
                'out_qty_reseller' => round($totalResellerQty, 2),
                'out_qty_other'    => round($totalOtherQty, 2),
            ];
        }

        // dd($final);
        return $final;
    }
    // public function getFinalComparison(array $filters = [])
    // {
    //     // رجّع البيانات مع مفتاح productId-storeId
    //     $inData  = collect($this->getInData($filters))->keyBy(fn($row) => $row['product_id'] . '-' . ($row['store_id'] ?? $filters['store_id'] ?? ''));
    //     $outData = collect($this->getOutData($filters))->keyBy(fn($row) => $row['product_id'] . '-' . ($row['store_id'] ?? $filters['store_id'] ?? ''));

    //     // dd($outData);
    //     $outQtyReseller = $out['out_qty_reseller'] ?? 0;
    //     $outQtyOther    = $out['out_qty_other'] ?? 0;
    //     $outQtyTotal    = $outQtyReseller + $outQtyOther;


    //     $allKeys = $inData->keys()->merge($outData->keys())->unique();

    //     $finalResult = [];

    //     foreach ($allKeys as $key) {
    //         [$pId, $storeId] = explode('-', $key);

    //         $in  = $inData->get($key);
    //         $out = $outData->get($key);

    //         $smallestUnit = $this->smallestUnit[$key] ?? $this->getSmallestUnitPrice($pId);

    //         $productName = $in['product_name'] ?? $out['product_name'] ?? '';
    //         $productCode = $in['product_code'] ?? $out['product_code'] ?? '';
    //         $unitName    = $in['unit_name'] ?? $out['unit_name'] ?? '';

    //         $inQty    = $in['qty'] ?? 0;
    //         $outQty   = $out['qty'] ?? 0;
    //         $inPrice  = $in['price'] ?? 0;
    //         $outPrice = $out['price'] ?? 0;

    //         $currentQty = 0;
    //         if ($smallestUnit) {
    //             $currentQty = MultiProductsInventoryService::getRemainingQty(
    //                 $pId,
    //                 $smallestUnit->unit_id,
    //                 $storeId
    //             );
    //         }

    //         $storeName = $in['store_name'] ?? $out['store_name'] ?? null;

    //         $finalResult[] = [
    //             'product_id'   => (int) $pId,
    //             'product_code' => $productCode,
    //             'product_name' => $productName,
    //             'store_id'     => (int) $storeId,
    //             'store_name'   => $storeName,
    //             'unit_name'    => $unitName,

    //             'in_qty'       => round($inQty, 2),
    //             'out_qty'      => round($outQty, 2),
    //             'difference'   => round($inQty - $outQty, 2),

    //             'in_price'     => round($inPrice, 2),
    //             'out_price'    => round($outPrice, 2),
    //             'current_qty'  => round($currentQty, 2),

    //             'out_qty_reseller'   => round($outQtyReseller, 2),
    //             'out_qty_other'      => round($outQtyOther, 2),
    //             'out_qty_total'      => round($outQtyTotal, 2),
    //         ];
    //     }
    //     dd($finalResult);
    //     return $finalResult;
    // }

    public function getFinalComparison(array $filters = [])
    {
        // رجّع البيانات مع مفتاح productId-storeId
        $inData  = collect($this->getInData($filters))
            ->keyBy(fn($row) => $row['product_id'] . '-' . ($row['store_id'] ?? $filters['store_id'] ?? ''));
        $outData = collect($this->getOutData($filters))
            ->keyBy(fn($row) => $row['product_id'] . '-' . ($row['store_id'] ?? $filters['store_id'] ?? ''));

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
