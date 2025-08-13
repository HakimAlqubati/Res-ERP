<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\ManufacturingInventoryReportResource;
use App\Services\Inventory\ManufacturingInventoryDetailReportService;
use Filament\Resources\Pages\ListRecords;

class ListManufacturingInventoryReport extends ListRecords
{
    protected static string $resource = ManufacturingInventoryReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.manufacturing-inventory-report';

    protected function getViewData(): array
    {
        $productId = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $onlySmallestUnit = $this->getTable()->getFilters()['options']->getState()['only_smallest_unit'] ?? false;

        if (!$productId || !$storeId) {
            return [
                'reportData' => [],
                'storeId' => $storeId,
            ];
        }
 
        $reportService = new ManufacturingInventoryDetailReportService();
        $reportData = $reportService->getDetailedRemainingStock(
            (int) $productId,
            (int) $storeId,
            (bool) $onlySmallestUnit
        );

        return [
            'reportData' => $reportData['batches'],
            'finalTotalValue' => $reportData['finalTotalValue'],
            'onlySmallestUnit' => $onlySmallestUnit,
            'storeId' => $storeId,
        ];
    }
}
