<?php

namespace App\Services\PurchasedReports;

use App\Models\InventoryTransaction;
use App\Models\UnitPrice;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceProductSummaryReportService
{
    public function getProductSummaryPerInvoice(array $filters = [])
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
                DB::raw('MIN(inventory_transactions.transactionable_id) as transactionable_id')

            )
            ->whereNull('inventory_transactions.deleted_at')

            ->where('inventory_transactions.movement_type', 'in')
            ->where('inventory_transactions.store_id', $filters['store_id'])
            // ->whereIn('inventory_transactions.transactionable_type', ['App\\Models\\PurchaseInvoice', 'App\\Models\\GoodsReceivedNote'])
        ;

        if (
            isset($filters['category_id']) ||
            isset($filters['only_manufacturing']) ||
            isset($filters['only_unmanufacturing'])
        ) {
            $query->join('categories', 'products.category_id', '=', 'categories.id');
        }

        // ✅ الفلاتر بعد التأكد من وجود join
        if (isset($filters['category_id'])) {
            $query->where('categories.id', $filters['category_id']);
        }

        if (isset($filters['only_manufacturing']) && $filters['only_manufacturing'] == 1) {
            $query->where('categories.is_manafacturing', true);
        }

        if (isset($filters['only_unmanufacturing']) && $filters['only_unmanufacturing'] == 1) {
            $query->where('categories.is_manafacturing', false);
        }
        // ✅ تطبيق فلتر واحد فقط (حسب الموجود)
        if (isset($filters['product_id'])) {
            $query->where('inventory_transactions.product_id', $filters['product_id']);
        }

        if (isset($filters['unit_id'])) {
            $query->where('inventory_transactions.unit_id', $filters['unit_id']);
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
        ];




        $query->groupBy(...$groupBy);

        $result = $query
            ->orderBy('transactionable_id', 'asc')
            ->get();
        if (isset($filters['details']) && $filters['details'] == true) {
            return $result;
        }
        $grouped = collect($result)->groupBy('product_id')->toArray();
        return $this->transformPurchasedGroupedResults($grouped);
        return $grouped;
    }





    public function getSmallestUnitPrice($productId)
    {
        return UnitPrice::where('product_id', $productId)->showInInvoices()
            ->orderBy('package_size', 'asc')->first();
    }



    public function transformPurchasedGroupedResults(array $grouped)
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
    // ✅ الدالة الجديدة لتنفيذ استعلام تتبع الطلبات المرتبطة بالمشتريات
    public function getOrderedProductsLinkedToPurchase(array $filters  = [])
    {
        $query = DB::table('inventory_transactions as it')
            ->join('products as p', 'it.product_id', '=', 'p.id')
            ->join('units as u', 'it.unit_id', '=', 'u.id')
            ->select(
                // 'it.transactionable_id as order_id',
                'it.product_id',
                'p.name as p_name',
                'u.name as unit',
                DB::raw('SUM(it.quantity) as qty'),
                'it.package_size',
                // 'it.source_transaction_id as source_id',
                // DB::raw('(SELECT transactionable_id FROM inventory_transactions WHERE id = it.source_transaction_id) as purchase_id')
            )
            ->whereNull('it.deleted_at')
            ->whereIn('it.source_transaction_id', function ($subquery) {
                $subquery->select('it1.source_transaction_id')
                    ->distinct()
                    ->from('inventory_transactions as it1')
                    ->whereIn('it1.transactionable_type', [
                        'App\\Models\\Order',
                        'App\\Models\\StockIssueOrder',
                        'App\\Models\\StockSupplyOrder',
                    ]);
            })
            ->where('it.movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->where('it.store_id', $filters['store_id']);

        if (isset($filters['product_id'])) {
            $query->where('it.product_id', $filters['product_id']);
        }
        $result = $query->groupBy(
            'it.product_id',
            'it.unit_id',
            'p.name',
            'u.name',
            'it.package_size'
        )
            ->get();

        if (isset($filters['details']) && $filters['details'] == true) {
            return $result;
        }

        $grouped = collect($result)->groupBy('product_id')->toArray();
        return $this->transformOrderedGroupedResults($grouped);
        return $grouped;
    }



    public function transformOrderedGroupedResults(array $grouped)
    {

        $final = [];

        foreach ($grouped as $productId => $entries) {
            $totalQty = 0;

            // Step 3: Get smallest unit definition
            $smallestUnit = $this->getSmallestUnitPrice($productId);
            if (!$smallestUnit) {
                continue;
            }

            $smallestPackageSize = $smallestUnit->package_size;
            $unitName = $smallestUnit->unit->name;
            $productName = $entries[0]->p_name; // object-style

            // Step 4: Convert each entry to smallest unit and sum
            foreach ($entries as $entry) {
                $multiplier = $entry->package_size / $smallestPackageSize;
                $convertedQty = $entry->qty * $multiplier;
                $totalQty += $convertedQty;
            }

            $final[] = [
                'product_id'   => $productId,
                'product_name' => $productName,
                'qty'          => round($totalQty, 1),
                'unit_name'    => $unitName,
            ];
        }

        return $final;
    }


    public function calculatePurchaseVsOrderedDifference(array $purchased, array $ordered)
    {

        $orderedMap = collect($ordered)->keyBy('product_id');
        $report = [];

        foreach ($purchased as $purchase) {
            $productId = $purchase['product_id'];
            $orderedQty = isset($orderedMap[$productId]) ? $orderedMap[$productId]['qty'] : 0;
            $difference = round($purchase['qty'] - $orderedQty, 2);
            $purchasedQty = round($purchase['qty'], 2);
            $orderedQty = round($orderedQty, 2);
            // if ($orderedQty > $purchasedQty) {
            $latestPrice = $this->getLatestPurchasePrice($productId);

            $lastPrice = ($latestPrice && $latestPrice->package_size > 0)
                ? ($latestPrice->price / $latestPrice->package_size)
                : $purchase['price'];
            $report[] = [
                'product_id'     => $productId,
                'product_name'   => $purchase['product_name'],
                'product_code'   => $purchase['product_code'],
                'unit_name'      => $purchase['unit_name'],
                'purchased_qty'  => $purchasedQty,
                'ordered_qty'    => $orderedQty,
                'difference'     => $difference,
                'unit_price'     => round($lastPrice, 2),
                'price'          => round($lastPrice * $difference, 2),
            ];
            // }
        }

        return $report;
    }

    public function getLatestPurchasePrice(int $productId)
    {

        $latestPrice = DB::table('purchase_invoice_details as pid')
            ->join('purchase_invoices as pi', 'pid.purchase_invoice_id', '=', 'pi.id')
            ->select('pid.price', 'pid.unit_id', 'pid.package_size')
            ->where('pid.product_id', $productId)
            ->whereNull('pid.deleted_at')
            ->whereNull('pi.deleted_at')
            ->orderByDesc('pid.id')
            ->first();

        if ($latestPrice) {
            return $latestPrice;
        }

        // ⛔️ لم نجد سعر في الفواتير، نبحث في unit_prices
        return DB::table('unit_prices')
            ->select('price', 'unit_id', 'package_size')
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->orderByDesc('id') // آخر وحدة
            ->first();

        // return DB::table('inventory_transactions')
        //     ->select('price', 'unit_id', 'package_size')
        //     ->where('product_id', $productId)
        //     ->where('movement_type', 'in')
        //     ->where('transactionable_type', 'App\\Models\\PurchaseInvoice')
        //     ->whereNull('deleted_at')
        //     ->orderByDesc('id')
        //     ->first();;
    }
}
