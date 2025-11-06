<?php

namespace App\Services\PurchasedReports;

use App\Models\GoodsReceivedNote;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\InventoryTransaction; // 👈 جديد
use Illuminate\Support\Facades\DB;

class GoodsReceivedNoteReportService
{
    public function getGrnDataWithPagination(
        array $productsIds = [],
        $storeId = 'all',
        $supplierId = 'all',
        array $grnNumbers = [],
        array $dateFilter = [],
        array $categoryIds = [],
        ?int $perPage = null
    ) {
        $store_name = 'All';
        $supplier_name = 'All';

        // 👈 تعبير السعر الموحّد: إذا كان سعر GRN = 0 خذ سعر الحركة، وإلا خذ سعر GRN
        $priceExpr = "CASE WHEN grn_details.price = 0 THEN COALESCE(inv.price, 0) ELSE grn_details.price END";

        $query = DB::table('goods_received_notes as grn')
            ->select(
                'grn_details.product_id as product_id',
                DB::raw("IF(JSON_VALID(products.name), REPLACE(JSON_EXTRACT(products.name, '$." . app()->getLocale() . "'), '\"', ''), products.name) AS product_name"),
                'units.name as unit_name',
                'products.code as product_code',
                'grn_details.quantity as quantity',
                DB::raw("$priceExpr as unit_price"), // 👈 تم الاستبدال هنا
                'grn.grn_date as grn_date',
                'grn.id as grn_id',
                'grn.grn_number as grn_number',
                'suppliers.name as supplier_name',
                'stores.name as store_name'
            )
            ->join('goods_received_note_details as grn_details', 'grn.id', '=', 'grn_details.grn_id')
            ->join('products', 'grn_details.product_id', '=', 'products.id')
            ->join('units', 'grn_details.unit_id', '=', 'units.id')
            ->leftJoin('suppliers', 'grn.supplier_id', '=', 'suppliers.id')
            ->join('stores', 'grn.store_id', '=', 'stores.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            // 👇 الربط مع inventory_transactions
            ->leftJoin('inventory_transactions as inv', function ($join) {
                $join->on('inv.transactionable_id', '=', 'grn.id')
                    ->on('inv.product_id', '=', 'grn_details.product_id')
                    ->on('inv.unit_id', '=', 'grn_details.unit_id')
                    ->where('inv.transactionable_type', '=', \App\Models\GoodsReceivedNote::class)
                    ->where('inv.movement_type', '=', InventoryTransaction::MOVEMENT_IN);
            });

        $query->where('status', GoodsReceivedNote::STATUS_APPROVED);

        // فلاتر
        if (!empty($categoryIds)) {
            $query->whereIn('products.category_id', $categoryIds);
        }

        if (is_numeric($storeId)) {
            $query->where('grn.store_id', $storeId);
            $store_name = optional(Store::find($storeId))->name ?? 'All';
        }

        if (is_numeric($supplierId)) {
            $query->where('grn.supplier_id', $supplierId);
            $supplier_name = optional(Supplier::find($supplierId))->name ?? 'All';
        }

        if (count($productsIds) > 0) {
            $query->whereIn('grn_details.product_id', $productsIds);
        }

        if (count($grnNumbers) > 0) {
            $query->whereIn('grn.grn_number', $grnNumbers);
        }

        if (!empty($dateFilter['start'])) {
            $query->whereDate('grn.grn_date', '>=', $dateFilter['start']);
        }
        if (!empty($dateFilter['end'])) {
            $query->whereDate('grn.grn_date', '<=', $dateFilter['end']);
        }

        // dd($dateFilter);
        // نسخة لحساب الإجمالي الكلي قبل التقسيم
        $rawQuery = clone $query;

        // 👈 نستخدم نفس تعبير السعر داخل الـ SUM
        $finalTotalAmount = $rawQuery->select(
            DB::raw("SUM(grn_details.quantity * ($priceExpr)) as total")
        )->value('total') ?? 0;

        $results = $perPage ? $query->paginate($perPage) : $query->get();

        $totalAmount = 0;
        foreach ($results as $item) {
            // $item->unit_price الآن هو الناتج من CASE WHEN أعلاه
            $item->formatted_unit_price = formatMoneyWithCurrency($item->unit_price);
            $item->formatted_quantity   = formatQunantity($item->quantity);
            $totalAmount += $item->unit_price * $item->quantity;
        }

        return [
            'results'            => $results,
            'supplier_name'      => $supplier_name,
            'store_name'         => $store_name,
            'total_amount'       => formatMoneyWithCurrency($totalAmount),
            'final_total_amount' => formatMoneyWithCurrency($finalTotalAmount),
        ];
    }
}
