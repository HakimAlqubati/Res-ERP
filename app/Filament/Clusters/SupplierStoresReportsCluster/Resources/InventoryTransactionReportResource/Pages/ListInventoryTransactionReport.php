<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactionReport extends ListRecords
{
    protected static string $resource = InventoryTransactionReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.inventory-report';

    protected function getViewData(): array
    {
        $productId = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;

        $product = Product::find($productId);

        $unitId = 'all';
        $reportData = [];
        if (isset($productId) && $productId != '') {
            $inventoryService = new InventoryService($productId, $unitId);
            // Get report for a specific product and unit
            $reportData = $inventoryService->getInventoryReport();
        }

        return ['reportData' => $reportData, 'product' => $product];
    }
}
