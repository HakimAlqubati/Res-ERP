<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages;

use App\Services\MultiProductsInventoryService;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource;
use App\Models\Store;
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
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'];

        $store = Store::find($storeId)?->name ?? null;
        $inventoryService = new MultiProductsInventoryService(
            storeId: $storeId,
            categoryId: null,
            productId: null,
            unitId: 'all',
            filterOnlyAvailable: false
        );
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantityًWithPagination(100);
        // dd(
        //     $lowStockProducts
        //     , $lowStockProducts->links()
        // );
        return [
            'reportData' => $lowStockProducts,
            'store' => $store

        ];
    }
}
