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
    protected string $view = 'filament.pages.halal-label-reports.halal-label-report';
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
