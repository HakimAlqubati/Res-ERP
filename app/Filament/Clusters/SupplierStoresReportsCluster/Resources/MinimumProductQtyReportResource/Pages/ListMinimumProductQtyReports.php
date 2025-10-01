<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages;

use App\Services\MultiProductsInventoryService;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource;
use App\Models\Product;
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
        $categoryId = $this->getTable()->getFilters()['category_id']->getState()['value'];

        $count = Product::active()->count(); 
        $store = Store::find($storeId)?->name ?? null;
        $inventoryService = new MultiProductsInventoryService(
            storeId: $storeId,
            categoryId: $categoryId,
            productId: null,
            unitId: 'all',
            filterOnlyAvailable: false
        );
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantityًWithPagination($count, true);
        // dd(
        //     $lowStockProducts,
        //     $lowStockProducts->links()
        // );
        return [
            'reportData' => $lowStockProducts,
            'store' => $store,
            // 'count' => $lowStockProducts['total'] ?? 0,

        ];
    }
}
