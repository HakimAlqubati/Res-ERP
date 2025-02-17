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
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class ListPurchaseInvoiceReport extends ListRecords
{
    protected static string $resource = PurchaseInvoiceReportResource::class;
    protected static string $view = 'filament.pages.stock-report.purchase-invoice-report-with-pagination';




    protected function getViewData(): array
    {
        $store_id = __filament_request_select('store_id', 'all');
        $supplier_id = __filament_request_select('supplier_id', 'all');
        $product_ids = [];
        $product_ids = __filament_request_select_multiple('product_id', [], true);
        $show_invoice_no = $this->getTable()->getFilters()['show_invoice_no']->getState()['isActive'];
        $invoice_no = __filament_request_select('invoice_no', 'all');
        $purchase_invoice_data = $this->getPurchasesInvoiceDataWithPagination($product_ids, $store_id, $supplier_id, $invoice_no);


        return [
            'purchase_invoice_data' => $purchase_invoice_data,
            'show_invoice_no' => $show_invoice_no,
        ];
    }



    public function getPurchasesInvoiceData($product_ids, $store_id, $supplier_id, $invoice_no)
    {
        $store_name = 'All';
        $supplier_name = 'All';
        $query = DB::table('purchase_invoices')
            ->select(
                'purchase_invoice_details.product_id as product_id',
                DB::raw("IF(JSON_VALID(products.name), REPLACE(JSON_EXTRACT(products.name, '$." . app()->getLocale() . "'), '\"', ''), products.name) AS product_name"),
                'units.name as unit_name',
                'purchase_invoice_details.quantity as quantity',
                'purchase_invoice_details.price as unit_price',
                'purchase_invoices.date as purchase_date',
                'purchase_invoices.id as purchase_invoice_id',
                'purchase_invoices.invoice_no as invoice_no',
                'users.name as supplier_name',
                'stores.name as store_name'
            )
            ->join('purchase_invoice_details', 'purchase_invoices.id', '=', 'purchase_invoice_details.purchase_invoice_id')
            ->join('products', 'purchase_invoice_details.product_id', '=', 'products.id')
            ->join('units', 'purchase_invoice_details.unit_id', '=', 'units.id')
            ->join('users', 'purchase_invoices.supplier_id', '=', 'users.id')
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
        if (isset($invoice_no) && $invoice_no != 'all') {
            $query->where('purchase_invoices.invoice_no', $invoice_no);
        }
        // $query->groupBy(
        //     'purchase_invoice_details.product_id',
        //     'purchase_invoice_details.unit_id',
        //     'products.name',
        //     'units.name',
        // );
        $query->whereNull('purchase_invoices.deleted_at');
        $results = $query->get();

        return [
            'results' => $results,
            'supplier_name' => $supplier_name,
            'store_name' => $store_name,
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

        $pdf = Pdf::loadView('export.reports.purchase-invoice-report', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("purchase-invoice-report" . '.pdf');
            }, "purchase-invoice-report" . '.pdf');
    }

    public function getPurchasesInvoiceDataWithPagination($product_ids, $store_id, $supplier_id, $invoice_no)
    {
        $store_name = 'All';
        $supplier_name = 'All';

        $query = DB::table('purchase_invoices')
            ->select(
                'purchase_invoice_details.product_id as product_id',
                DB::raw("IF(JSON_VALID(products.name), REPLACE(JSON_EXTRACT(products.name, '$." . app()->getLocale() . "'), '\"', ''), products.name) AS product_name"),
                'units.name as unit_name',
                'purchase_invoice_details.quantity as quantity',
                'purchase_invoice_details.price as unit_price',
                'purchase_invoices.date as purchase_date',
                'purchase_invoices.id as purchase_invoice_id',
                'purchase_invoices.invoice_no as invoice_no',
                'users.name as supplier_name',
                'stores.name as store_name'
            )
            ->join('purchase_invoice_details', 'purchase_invoices.id', '=', 'purchase_invoice_details.purchase_invoice_id')
            ->join('products', 'purchase_invoice_details.product_id', '=', 'products.id')
            ->join('units', 'purchase_invoice_details.unit_id', '=', 'units.id')
            ->join('users', 'purchase_invoices.supplier_id', '=', 'users.id')
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

        if (isset($invoice_no) && $invoice_no !== 'all') {
            $query->where('purchase_invoices.invoice_no', $invoice_no);
        }

        $query->whereNull('purchase_invoices.deleted_at');

        // 🔹 Apply pagination (change `10` to the number of records per page)
        $results = $query->paginate(15);

        return [
            'results' => $results,
            'supplier_name' => $supplier_name,
            'store_name' => $store_name,
        ];
    }
}
