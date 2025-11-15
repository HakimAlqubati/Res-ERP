<?php

namespace App\Filament\Resources\Reports\Pages;

use Filament\Actions\Action;
use App\Filament\Resources\Reports\GoodsReceivedNoteReportResource;
use App\Services\PurchasedReports\GoodsReceivedNoteReportService;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ListGoodsReceivedNoteReport extends ListRecords
{
    protected static string $resource = GoodsReceivedNoteReportResource::class;
    protected string $view = 'filament.pages.stock-report.grn-report-with-pagination';

    public $perPage = 15;
    protected $updatesQueryString = ['perPage'];

    protected function getViewData(): array
    {
        $perPage = $this->perPage;
        if ($perPage === 'all') $perPage = 9999;

        $showGrnNo = $this->getTable()->getFilters()['show_grn_number']->getState()['isActive'] ?? false;

        $productsIds = $this->getTable()->getFilters()['product_id']->getState()['values'] ?? [];
        $grnNumbers  = $this->getTable()->getFilters()['grn_number']->getState()['values'] ?? [];
        $supplierId  = $this->getTable()->getFilters()['supplier_id']->getState()['value'] ?? 'all';
        $storeId     = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? 'all';
        $categoryIds = $this->getTable()->getFilters()['category_id']->getState()['values'] ?? [];
        $dateRange   = $this->getTable()->getFilters()['date']->getState() ?? [];

        // dd($dateRange);
        $data = (new GoodsReceivedNoteReportService())->getGrnDataWithPagination(
            $productsIds,
            $storeId,
            $supplierId,
            $grnNumbers,
            $dateRange,
            $categoryIds,
            $perPage
        );

        return [
            'grn_data'            => $data,
            'total_amount'        => $data['total_amount'],
            'final_total_amount'  => $data['final_total_amount'],
            'show_grn_number'     => $showGrnNo,
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('Export to PDF')
                ->label(__('lang.export_pdf'))
                ->action('exportToPdf')
                ->hidden()
                ->color('success'),
        ];
    }

    public function exportToPdf()
    {
        $data = $this->getViewData();

        $pdf = PDF::loadView('export.reports.grn-report', [
            'grn_data'        => $data['grn_data'],
            'show_grn_number' => $data['show_grn_number'],
        ]);

        return response()->streamDownload(function () use ($pdf) {
            $pdf->stream("grn-report.pdf");
        }, "grn-report.pdf");
    }
}
