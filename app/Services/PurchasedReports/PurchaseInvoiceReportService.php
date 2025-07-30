<?php
namespace App\Services\PurchasedReports;

use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceReportService
{
    public function getPurchasesInvoiceDataWithPagination(
        $productsIds,
        $storeId,
        $supplierId,
        $invoiceNos,

        $dateFilter = [],
        $categoryIds = [],
        $perPage = null
    ) {
        $store_name    = 'All';
        $supplier_name = 'All';

        $query = DB::table('purchase_invoices')
            ->select(
                'purchase_invoice_details.product_id as product_id',
                DB::raw("IF(JSON_VALID(products.name), REPLACE(JSON_EXTRACT(products.name, '$." . app()->getLocale() . "'), '\"', ''), products.name) AS product_name"),
                'units.name as unit_name',
                'products.code as product_code',
                'purchase_invoice_details.quantity as quantity',
                'purchase_invoice_details.price as unit_price',
                'purchase_invoices.date as purchase_date',
                'purchase_invoices.id as purchase_invoice_id',
                'purchase_invoices.invoice_no as invoice_no',
                'suppliers.name as supplier_name',
                'stores.name as store_name'
            )
            ->join('purchase_invoice_details', 'purchase_invoices.id', '=', 'purchase_invoice_details.purchase_invoice_id')
            ->join('products', 'purchase_invoice_details.product_id', '=', 'products.id')
            ->join('units', 'purchase_invoice_details.unit_id', '=', 'units.id')
            ->leftJoin('suppliers', 'purchase_invoices.supplier_id', '=', 'suppliers.id')
            ->join('stores', 'purchase_invoices.store_id', '=', 'stores.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')

            ->where('purchase_invoices.active', 1);

        if (! empty($categoryIds)) {
            $query->whereIn('products.category_id', $categoryIds);
        }

        if (is_numeric($storeId)) {
            $query->where('purchase_invoices.store_id', $storeId);
            $store_name = Store::find($storeId)?->name;
        }

        if (is_numeric($supplierId)) {
            $query->where('purchase_invoices.supplier_id', $supplierId);
            $supplier_name = Supplier::find($supplierId)?->name;
        }

        if (count($productsIds) > 0) {
            $query->whereIn('purchase_invoice_details.product_id', $productsIds);
        }

        if (count($invoiceNos) > 0) {
            $query->whereIn('purchase_invoices.invoice_no', $invoiceNos);
        }

        if (! empty($dateFilter['from'])) {
            $query->whereDate('purchase_invoices.date', '>=', $dateFilter['from']);
        }
        if (! empty($dateFilter['to'])) {
            $query->whereDate('purchase_invoices.date', '<=', $dateFilter['to']);
        }

        // قبل paginate
        $rawQuery = clone $query;

// احسب الإجمالي الكلي من النسخة غير المقسّمة
        $finalTotalAmount = $rawQuery->select(
            DB::raw('SUM(purchase_invoice_details.quantity * purchase_invoice_details.price) as total')
        )->value('total') ?? 0;

        $results = $perPage ? $query->paginate($perPage) : $query->get();
        $totalAmount = 0;
        foreach ($results as $item) {
            $item->unit_price           = $item->unit_price;
            $item->quantity             = $item->quantity;
            $item->formatted_unit_price = formatMoneyWithCurrency($item->unit_price);
            $item->formatted_quantity   = formatQunantity($item->quantity);
            $totalAmount += $item->unit_price * $item->quantity;

        }
        return [
            'results'       => $results,
            'supplier_name' => $supplier_name,
            'total_amount'  => formatMoneyWithCurrency($totalAmount),
            'store_name'    => $store_name,
            'final_total_amount'  => formatMoneyWithCurrency($finalTotalAmount),

        ];
    }
}