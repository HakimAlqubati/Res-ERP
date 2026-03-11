<?php

namespace App\Filament\Resources\HalalLabelReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Resources\HalalLabelReportResource;
use App\Models\Store;
use App\Services\StockSupply\Reports\ManufacturingProductLabelReportsService;
use Filament\Resources\Pages\ListRecords;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ListHalalLabelReports extends ListRecords
{
    use HasBackButtonAction;
    protected static string $resource = HalalLabelReportResource::class;
    public static string $viewModel2 = 'filament.pages.halal-label-reports.halal-label-report-model-2';
    public static string $viewModel1 = 'filament.pages.halal-label-reports.halal-label-report';
    // protected string $view = 'filament.pages.halal-label-reports.halal-label-report';

    public function getView(): string
    {
        $viewModel = $this->getTable()->getFilters()['view_model']->getState()['value'] ?? 'model_1';
        return $viewModel === 'model_2' ? self::$viewModel2 : self::$viewModel1;
    }
    public $selectedLabelDetails = null;

    public function showDetails($productId, $batchCode)
    {
        $this->selectedLabelDetails = null;
        $service = new ManufacturingProductLabelReportsService();
        $this->selectedLabelDetails = $service->getLabelDetails($productId, $batchCode);
        $this->dispatch('open-modal', id: 'label-details-modal');
    }


    protected function getViewData(): array
    {
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $productId = $this->getTable()->getFilters()['product_id']->getState()['values'] ?? null;
        $startDate = $this->getTable()->getFilters()['date_range']->getState()['start_date'];
        $endDate = $this->getTable()->getFilters()['date_range']->getState()['end_date'];

        $filters = [
            'store_id' => $storeId,
            'product_id' => $productId,
            'from_date' => $startDate,
            'to_date' => $endDate,
        ];

        $service = new ManufacturingProductLabelReportsService();
        // Fetching up to 1000 records for the report
        $reportPaginator = $service->getLabelsReport($filters, 1000);

        $data = $reportPaginator->items(); // Get array of items

        $store = Store::find($storeId)?->name ?? null;

        return [
            'reportData' => $data, // Array of arrays/objects
            'store' => $store,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    public function exportPdf()
    {
        $data = $this->getViewData();

        $pdf = PDF::loadView('export.reports.halal-label-report-model-2-pdf', [
            'reportData' => $data['reportData'],
            'store' => $data['store'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
        ], [], [
            'format'        => [100, 55],  // Custom sticker size: 100mm x 55mm (Zebra ZD230)
            'margin_left'   => 2,
            'margin_right'  => 2,
            'margin_top'    => 2,
            'margin_bottom' => 2,
        ]);

        $storeName = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $data['store'] ?? 'All_Stores');
        $storeName = str_replace(' ', '_', $storeName);
        $fileName = "Halal_Label_{$storeName}_{$data['startDate']}_to_{$data['endDate']}.pdf";

        return response()->streamDownload(function () use ($pdf, $fileName) {
            $pdf->stream($fileName);
        }, $fileName);
    }
}
