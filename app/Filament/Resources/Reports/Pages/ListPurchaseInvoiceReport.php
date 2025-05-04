<?php

namespace App\Filament\Resources\PurchaseInvoiceReportResource\Reports\Pages;

use App\Filament\Resources\Reports\PurchaseInvoiceReportResource;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Store;
use App\Models\Supplier;

use Filament\Forms\Components\Builder;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;

class ListPurchaseInvoiceReport extends ListRecords
{
    protected static string $resource = PurchaseInvoiceReportResource::class;
    protected static string $view = 'filament.pages.stock-report.purchase-invoice-report-with-pagination';




    protected function getViewData(): array
    {
        $perPage = request()->get('perPage', 20);
        if ($perPage === 'all') {
            $perPage = 9999; // سيتم إرجاع كل النتائج
        }
        $product_ids = [];
        $invoiceNos = [];
        // $product_ids = __filament_request_select_multiple('product_id', [], true);
        $show_invoice_no = $this->getTable()->getFilters()['show_invoice_no']->getState()['isActive'];

        $product_ids = $this->getTable()->getFilters()['product_id']->getState()['values'] ?? [];
        $invoiceNos = $this->getTable()->getFilters()['invoice_no']->getState()['values'] ?? [];
        $supplier_id = $this->getTable()->getFilters()['supplier_id']->getState()['value'] ?? 'all';
        $store_id = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? 'all';

        $purchase_invoice_data = $this->getPurchasesInvoiceDataWithPagination(
            $product_ids,
            $store_id,
            $supplier_id,
            $invoiceNos,
            $perPage
        );


        return [
            'purchase_invoice_data' => $purchase_invoice_data,
            'show_invoice_no' => $show_invoice_no,
        ];
    }



    protected function getActions(): array
    {
        return  [Action::make('Export to PDF')->label(__('lang.export_pdf'))
            ->action('exportToPdf')->hidden()
            ->color('success'),];
    }

    public function exportToPdf()
    {
        $data = $this->getViewData();
        $data =    [
            'purchase_invoice_data' => $data['purchase_invoice_data'],
            'show_invoice_no' => $data['show_invoice_no'],
        ];

        $pdf = PDF::loadView('export.reports.purchase-invoice-report', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("purchase-invoice-report" . '.pdf');
            }, "purchase-invoice-report" . '.pdf');
    }

    public function getPurchasesInvoiceDataWithPagination($product_ids, $store_id, $supplier_id, $invoiceNos, $perPage = 20)
    {
        $store_name = 'All';
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
            ->where('purchase_invoices.active', 1);

        if (is_numeric($store_id)) {
            $query->where('purchase_invoices.store_id', $store_id);
            $store_name = Store::find($store_id)?->name;
        }

        if (is_numeric($supplier_id)) {
            $query->where('purchase_invoices.supplier_id', $supplier_id);
            $supplier_name = Supplier::find($supplier_id)?->name;
        }

        if (count($product_ids) > 0) {
            $query->whereIn('purchase_invoice_details.product_id', $product_ids);
        }

        if (count($invoiceNos) > 0) {
            $query->whereIn('purchase_invoices.invoice_no', $invoiceNos);
        }

        $query->whereNull('purchase_invoices.deleted_at');

        // 🔹 Apply pagination (change `10` to the number of records per page)
        $results = $query->paginate($perPage);

        return [
            'results' => $results,
            'supplier_name' => $supplier_name,
            'store_name' => $store_name,
        ];
    }
}
