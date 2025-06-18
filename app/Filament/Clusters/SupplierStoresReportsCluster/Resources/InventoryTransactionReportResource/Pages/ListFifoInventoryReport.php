<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\FifoInventoryReportResource;
use App\Services\Inventory\FifoInventoryDetailReportService;
use Filament\Resources\Pages\ListRecords;

class ListFifoInventoryReport extends ListRecords
{
    protected static string $resource = FifoInventoryReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.fifo-inventory-report';

    protected function getViewData(): array
    {
        $productId = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;

        if (!$productId || !$storeId) {
            return [
                'reportData' => [],
                'storeId' => $storeId,
            ];
        }

        $reportService = new FifoInventoryDetailReportService();
        $reportData = $reportService->getDetailedRemainingStock((int)$productId, (int)$storeId);

        return [
            'reportData' => $reportData,
            'storeId' => $storeId,
        ];
    }
}
