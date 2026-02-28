<?php

namespace App\Filament\Resources\HalalLabelReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Resources\HalalLabelReportResource;
use App\Models\Store;
use App\Services\StockSupply\Reports\ManufacturingProductLabelReportsService;
use Filament\Resources\Pages\ListRecords;

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
}
