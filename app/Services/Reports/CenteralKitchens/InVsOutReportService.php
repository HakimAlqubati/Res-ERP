<?php

namespace App\Services\Reports\CenteralKitchens;

use App\Models\UnitPrice;
use App\Services\MultiProductsInventoryService;
use Illuminate\Support\Facades\DB;

class InVsOutReportService
{
    private array $smallestUnit = [];
    public function getInData(array $filters = [])
    {

        $query = DB::table('inventory_transactions')
            ->join('products', 'inventory_transactions.product_id', '=', 'products.id')
            ->join('units', 'inventory_transactions.unit_id', '=', 'units.id')
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
        ];



        $query->groupBy(...$groupBy);

        $result = $query
            ->get();
        if (isset($filters['details']) && $filters['details'] == true) {
            return $result;
        }
        $grouped = collect($result)->groupBy('product_id')->toArray();
        return $this->transformInResults($grouped);
        return $grouped;
    }

    public function transformInResults(array $grouped)
    {
        $final = [];

        foreach ($grouped as $productId => $entries) {
            $totalQty = 0;
            $totalCost = 0;

            // الحصول على الوحدة الصغيرة وكود العبوة الخاصة بها
            $smallestUnit = $this->getSmallestUnitPrice($productId);
            if (!$smallestUnit) {
                continue; // تجاهل المنتج لو ما عنده وحدة معرفة
            }

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
            }

            $final[] = [
                'product_id'   => $productId,
                'product_code'   => $productCode,
                'product_name' => $productName,
                'product_name' => $productName,
                'qty'          => round($totalQty, 2),
                'unit_name'    => $unitName,
                'price' => round($totalCost, 2),
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
        ];

        $query->groupBy(...$groupBy);

        $result = $query
            ->get();

        if (isset($filters['details']) && $filters['details'] == true) {
            return $result;
        }

        $grouped = collect($result)->groupBy('product_id')->toArray();
        return $this->transformOutResults($grouped);
    }
    public function transformOutResults(array $grouped)
    {
        $final = [];

        foreach ($grouped as $productId => $entries) {
            $totalQty = 0;
            $totalCost = 0;

            $smallestUnit = $this->getSmallestUnitPrice($productId);
            $this->smallestUnit[$productId] = $smallestUnit;
            if (!$smallestUnit) {
                continue;
            }

            $smallestPackageSize = $smallestUnit->package_size;
            $unitName = $smallestUnit->unit->name;

            $productName = $entries[0]->product_name;
            $productCode = $entries[0]->product_code;

            foreach ($entries as $entry) {
                $multiplier = $entry->package_size / $smallestPackageSize;
                $convertedQty = $entry->qty * $multiplier;
                $totalQty += $convertedQty;
                $totalCost = $entry->price / $entry->package_size;
            }

            $final[] = [
                'product_id'   => $productId,
                'product_code' => $productCode,
                'product_name' => $productName,
                'qty'          => round($totalQty, 2),
                'unit_name'    => $unitName,
                'price'        => round($totalCost, 2),
            ];
        }

        return $final;
    }

    public function getFinalComparison(array $filters = [])
    {
        $inData = collect($this->getInData($filters))->keyBy('product_id');
        $outData = collect($this->getOutData($filters))->keyBy('product_id');

        $allProductIds = $inData->keys()->merge($outData->keys())->unique();

        $finalResult = [];

        foreach ($allProductIds as $productId) {
            $in = $inData->get($productId);
            $out = $outData->get($productId);

            $productName = $in['product_name'] ?? $out['product_name'] ?? '';
            $productCode = $in['product_code'] ?? $out['product_code'] ?? '';
            $unitName = $in['unit_name'] ?? $out['unit_name'] ?? '';

            $inQty = $in['qty'] ?? 0;
            $outQty = $out['qty'] ?? 0;

            $inPrice = $in['price'] ?? 0;
            $outPrice = $out['price'] ?? 0;

            $currentQty = 0;
            if (!empty($filters['store_id'])) {
                $smallestUnit = $this->smallestUnit[$productId] ?? null;
                if ($smallestUnit) {
                    $currentQty = MultiProductsInventoryService::getRemainingQty(
                        $productId,
                        $smallestUnit->unit_id, // نحاول نمرر unit_id
                        $filters['store_id']
                    );
                }
            }
         
            // dd($currentQty,$this->smallestUnit  );
            $finalResult[] = [
                'product_id'   => $productId,
                'product_code' => $productCode,
                'product_name' => $productName,
                'unit_name'    => $unitName,

                'in_qty'       => round($inQty, 2),
                'out_qty'      => round($outQty, 2),
                'difference'   => round($inQty - $outQty, 2),

                'in_price'     => round($inPrice, 2),
                'out_price'    => round($outPrice, 2),
                'current_qty'  => round($currentQty, 2),
            ];
        }

        return $finalResult;
    }
}
