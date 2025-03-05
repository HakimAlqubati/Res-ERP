<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource;
use Filament\Resources\Pages\ListRecords;

class ListMinimumProductQtyReports extends ListRecords
{
    protected static string $resource = MinimumProductQtyReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.minimum-products-report';
    protected function getHeaderActions(): array
    {
        return [];
    }
    protected function getViewData(): array
    {
        $inventoryService = new \App\Services\MultiProductsInventoryService();
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantityًWithPagination(15);


        return ['reportData' => $lowStockProducts];
    }
}
