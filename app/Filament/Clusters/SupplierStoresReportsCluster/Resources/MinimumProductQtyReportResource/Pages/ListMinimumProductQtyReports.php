<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages;

use App\Services\MultiProductsInventoryService;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource;
use Filament\Resources\Pages\ListRecords;

class ListMinimumProductQtyReports extends ListRecords
{
    protected static string $resource = MinimumProductQtyReportResource::class;
    protected string $view = 'filament.pages.inventory-reports.minimum-products-report';
    protected function getHeaderActions(): array
    {
        return [];
    }
    protected function getViewData(): array
    {
        $inventoryService = new MultiProductsInventoryService(storeId: 0);
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantityًWithPagination();
        // dd(
        //     $lowStockProducts
        //     , $lowStockProducts->links()
        // );
        return ['reportData' => $lowStockProducts];
    }
}
