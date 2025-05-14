<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\MultiProductsInventoryService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactionReport extends ListRecords
{
    protected static string $resource = InventoryTransactionReportResource::class;
    // protected static string $view = 'filament.pages.inventory-reports.inventory-report';
    protected static string $view = 'filament.pages.inventory-reports.multi-products-inventory-report';

    protected function getViewData(): array
    {
        $productId = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $categoryId = $this->getTable()->getFilters()['category_id']->getState()['value'] ?? null;
        $showAvailableInStock = $this->getTable()->getFilters()['show_extra_fields']->getState()['only_available'];
        $unitId = 'all';
        $inventoryService = new MultiProductsInventoryService($categoryId, $productId, $unitId, $storeId, $showAvailableInStock);

        // ⬅️ احصل على القيمة من الاستعلام أو استخدم 15 كقيمة افتراضية
        $perPage = request()->get('perPage', 15);
        if ($perPage === 'all') {
            $perPage = 9999; // سيتم إرجاع كل النتائج
        }

        // Get paginated report data
        $report = $inventoryService->getInventoryReportWithPagination($perPage);
        
        $reportData = $report['reportData'] ?? $report;
        $pagination = $report['pagination'] ?? $report;


        return ['reportData' => $reportData, 'pagination' => $pagination];
    }
}
