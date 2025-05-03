<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MissingInventoryProductsReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MissingInventoryProductsReportResource;
use App\Services\StockInventoryReportService;
use Filament\Resources\Pages\ListRecords;

class ListMissingInventoryProductsReport extends ListRecords
{
    protected static string $resource = MissingInventoryProductsReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.missing-inventory-products';
    protected function getHeaderActions(): array
    {
        return [];
    }
    protected function getViewData(): array
    {
        $start = $this->getTable()->getFilters()['date_range']->getState()['start_date'];
        $end = $this->getTable()->getFilters()['date_range']->getState()['end_date'];
        $products = StockInventoryReportService::getProductsNotInventoriedBetween($start, $end);

        return [
            'reportData' => $products,
            'startDate' => $start,
            'endDate' => $end,
        ];
    }
}
