<?php

namespace App\Services\PurchasedReports;

use App\Models\UnitPrice;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceProductSummaryReportService
{
    public function getProductSummaryPerInvoice(array $filters = [], bool $groupByInvoice = false, bool $groupByPrice = false)
    {
        if (isset($filters['group_by_invoice']) && $filters['group_by_invoice'] == 1) {
            $groupByInvoice = 1;
        }

        $query = DB::table('inventory_transactions')
            ->join('products', 'inventory_transactions.product_id', '=', 'products.id')
            ->join('units', 'inventory_transactions.unit_id', '=', 'units.id')
            ->join('purchase_invoices', 'inventory_transactions.transactionable_id', '=', 'purchase_invoices.id')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.code as product_code',
                'units.name as unit_name',
                'inventory_transactions.package_size',
                DB::raw('SUM(inventory_transactions.quantity) as qty'),
                DB::raw('SUM(inventory_transactions.price) as price')
            )
            ->whereNotIn('inventory_transactions.product_id', [116])
            ->where('inventory_transactions.movement_type', 'in')
            ->where('inventory_transactions.transactionable_type', 'App\\Models\\PurchaseInvoice');

        // ✅ تطبيق فلتر واحد فقط (حسب الموجود)
        if (isset($filters['product_id'])) {
            $query->where('inventory_transactions.product_id', $filters['product_id']);
        }

        if (isset($filters['unit_id'])) {
            $query->where('inventory_transactions.unit_id', $filters['unit_id']);
        }

        if (isset($filters['purchase_invoice_id'])) {
            $query->where('inventory_transactions.transactionable_id', $filters['purchase_invoice_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('purchase_invoices.date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('purchase_invoices.date', '<=', $filters['date_to']);
        }

        // ✅ الأعمدة الإضافية حسب خيارات التجميع
        if ($groupByInvoice) {
            $query->addSelect('inventory_transactions.transactionable_id as purchase_invoice_id');
        }

        if ($groupByPrice) {
            $query->addSelect('inventory_transactions.price as unit_price');
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

        if ($groupByInvoice) {
            $groupBy[] = 'inventory_transactions.transactionable_id';
        }

        if ($groupByPrice) {
            $groupBy[] = 'inventory_transactions.price';
        }

        $query->groupBy(...$groupBy);

        $result = $query
            ->orderBy('inventory_transactions.transactionable_id', 'asc')
            ->get();
        if (isset($filters['details']) && $filters['details'] == true) {
            return $result;
        }
        $grouped = collect($result)->groupBy('product_id')->toArray();
        return $this->transformPurchasedGroupedResults($grouped);
        return $grouped;
    }


    public function getProductSummaryPerExcelImport(array $filters = [], bool $groupByInvoice = false, bool $groupByPrice = false)
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
                DB::raw('SUM(inventory_transactions.price) as price')
            )
            ->whereNotIn('inventory_transactions.product_id', [116])
            ->where('inventory_transactions.movement_type', 'in')
            ->where('inventory_transactions.transactionable_type', 'ExcelImport');

        // ✅ تطبيق فلتر واحد فقط (حسب الموجود)
        if (isset($filters['product_id'])) {
            $query->where('inventory_transactions.product_id', $filters['product_id']);
        }

        if (isset($filters['unit_id'])) {
            $query->where('inventory_transactions.unit_id', $filters['unit_id']);
        }

        if (isset($filters['purchase_invoice_id'])) {
            $query->where('inventory_transactions.transactionable_id', $filters['purchase_invoice_id']);
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

        if ($groupByInvoice) {
            $groupBy[] = 'inventory_transactions.transactionable_id';
        }

        if ($groupByPrice) {
            $groupBy[] = 'inventory_transactions.price';
        }

        $query->groupBy(...$groupBy);

        $result = $query
            ->orderBy('inventory_transactions.transactionable_id', 'asc')
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
            ->whereNotIn('it.product_id', [116])
            ->whereIn('it.source_transaction_id', function ($subquery) {
                $subquery->select('it1.source_transaction_id')
                    ->distinct()
                    ->from('inventory_transactions as it1')
                    ->where('it1.transactionable_type', 'App\\Models\\Order')
                    ->whereExists(function ($existsQuery) {
                        $existsQuery->select(DB::raw(1))
                            ->from('inventory_transactions as it2')
                            ->whereRaw('it2.id = it1.source_transaction_id')
                            ->where('it2.transactionable_type', 'App\\Models\\PurchaseInvoice');
                    });
            });

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
                'qty'          => round($totalQty, 2),
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
            if ($orderedQty >= $purchasedQty) {
                $report[] = [
                    'product_id'     => $productId,
                    'product_name'   => $purchase['product_name'],
                    'product_code'   => $purchase['product_code'],
                    'unit_name'      => $purchase['unit_name'],
                    'purchased_qty'  => $purchasedQty,
                    'ordered_qty'    => $orderedQty,
                    'difference'     => $difference,
                    'unit_price' => $purchase['price'],
                    'price' => $purchase['price'] * $difference,
                ];
            }
        }

        return $report;
    }


    public function getOrderedProductsFromExcelImport(array $filters = [])
    {
        $query = DB::table('inventory_transactions as it')
            ->join('products as p', 'it.product_id', '=', 'p.id')
            ->join('units as u', 'it.unit_id', '=', 'u.id')
            ->select(
                'it.product_id',
                'p.name as p_name',
                'u.name as unit',
                DB::raw('SUM(it.quantity) as qty'),
                'it.package_size'
            )
            ->whereNotIn('it.product_id', [116])
            ->where('it.transactionable_type', 'ExcelImport');

        if (isset($filters['product_id'])) {
            $query->where('it.product_id', $filters['product_id']);
        }

        $result = $query->groupBy(
            'it.product_id',
            'it.unit_id',
            'p.name',
            'u.name',
            'it.package_size'
        )->get();

        if (isset($filters['details']) && $filters['details'] == true) {
            return $result;
        }

        $grouped = collect($result)->groupBy('product_id')->toArray();
        return $this->transformOrderedGroupedResults($grouped);
    }
}
