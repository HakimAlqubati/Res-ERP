<?php

namespace App\Filament\Resources\Reports\Pages;

use Filament\Actions\Action;
use App\Filament\Resources\Reports\PurchaseInvoiceReportResource;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\PurchasedReports\PurchaseInvoiceReportService;
use Filament\Forms\Components\Builder;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;

class ListPurchaseInvoiceReport extends ListRecords
{
    protected static string $resource = PurchaseInvoiceReportResource::class;
    protected string $view = 'filament.pages.stock-report.purchase-invoice-report-with-pagination';
    public $perPage = 15;
    protected $updatesQueryString = ['perPage'];




    protected function getViewData(): array
    {
        
        $perPage = $this->perPage;

        if ($perPage === 'all') {
            $perPage = 9999; // أو أي عدد كبير جدًا لضمان عرض الكل
        }
 
        $productsIds = [];
        $invoiceNos = [];

        $showInvoiceNo = $this->getTable()->getFilters()['show_invoice_no']->getState()['isActive'];

        $productsIds = $this->getTable()->getFilters()['product_id']->getState()['values'] ?? [];
        $invoiceNos = $this->getTable()->getFilters()['invoice_no']->getState()['values'] ?? [];
        $supplierId = $this->getTable()->getFilters()['supplier_id']->getState()['value'] ?? 'all';
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? 'all';
        $categoryIds = $this->getTable()->getFilters()['category_id']->getState()['values'] ?? [];

        $purchaseInvoiceData = (new PurchaseInvoiceReportService())->getPurchasesInvoiceDataWithPagination(
            $productsIds,
            $storeId,
            $supplierId,
            $invoiceNos,
            $this->getTable()->getFilters()['date']->getState() ?? [],
            $categoryIds,
            $perPage 
        );

        // dd($purchaseInvoiceData);
 

        return [
            'purchase_invoice_data' => $purchaseInvoiceData,
            'total_amount' => $purchaseInvoiceData['total_amount'],
            'show_invoice_no' => $showInvoiceNo,
            'final_total_amount' => $purchaseInvoiceData['final_total_amount'],
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

 
}