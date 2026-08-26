<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryValuationReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryValuationReportResource;
use App\Filament\Traits\HasBackButtonAction;
use App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts\StockInventoryValuationServiceInterface;
use Filament\Resources\Pages\ListRecords;

class ListStockInventoryValuationReport extends ListRecords
{
    use HasBackButtonAction;

    protected static string $resource = StockInventoryValuationReportResource::class;

    protected string $view = 'filament.pages.inventory-reports.stock-inventory-valuation-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();
        $filterState = $filters['valuation_filter']->getState() ?? [];

        $storeId       = $filterState['store_id'] ?? null;
        $inventoryDate = $filterState['inventory_date'] ?? null;

        if (! $storeId || ! $inventoryDate) {
            return [
                'storeId'       => $storeId,
                'inventoryDate' => $inventoryDate,
                'reportData'    => null,
            ];
        }

        $service    = app(StockInventoryValuationServiceInterface::class);
        $reportData = $service->getReport((int) $storeId, (string) $inventoryDate);

        return [
            'storeId'       => $storeId,
            'inventoryDate' => $inventoryDate,
            'reportData'    => $reportData,
        ];
    }
}
