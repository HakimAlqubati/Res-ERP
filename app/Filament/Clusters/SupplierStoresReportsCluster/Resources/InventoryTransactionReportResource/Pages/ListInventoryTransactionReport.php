<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\MultiProductsInventoryService;
use App\Services\Inventory\Optimized\OptimizedInventoryService;
use App\Services\Inventory\Optimized\DTOs\InventoryFilterDto;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactionReport extends ListRecords
{
    use HasBackButtonAction;
    protected static string $resource = InventoryTransactionReportResource::class;
    protected string $view = 'filament.pages.inventory-reports.multi-products-inventory-report';

    public $perPage = 15;

    /**
     * استخدام الكلاس المُحسّن للمخزون
     * true = OptimizedInventoryService (الجديد - أسرع)
     * false = MultiProductsInventoryService (القديم)
     */
    public bool $useOptimizedService = false;

    protected function getViewData(): array
    {
        $productIds = $this->getTable()->getFilters()['product_id']->getState()['values'] ?? [];

        if (!is_array($productIds)) {
            $productIds = [$productIds];
        }
        $productId = $productIds[0] ?? null;
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'];
        $categoryId = $this->getTable()->getFilters()['category_id']->getState()['value'] ?? null;
        $showAvailableInStock = $this->getTable()->getFilters()['show_extra_fields']->getState()['only_available'];
        $unitId = 'all';

        $perPage = $this->perPage;
        if ($perPage === 'all') {
            $perPage = 9999;
        }

        // اختيار الكلاس حسب الخاصية
        if ($this->useOptimizedService) {
            $report = $this->getOptimizedReport($categoryId, $productId, $productIds, $unitId, $storeId, $showAvailableInStock, $perPage);
        } else {
            $report = $this->getLegacyReport($categoryId, $productId, $productIds, $unitId, $storeId, $showAvailableInStock, $perPage);
        }

        $reportData = $report['reportData'] ?? $report;
        $pagination = $report['pagination'] ?? $report;

        return [
            'reportData' => $reportData,
            'storeId' => $storeId,
            'pagination' => $pagination
        ];
    }

    /**
     * استخدام الكلاس المُحسّن الجديد
     */
    protected function getOptimizedReport($categoryId, $productId, $productIds, $unitId, $storeId, $showAvailableInStock, $perPage): array
    {
        $filter = new InventoryFilterDto(
            storeId: (int) $storeId,
            categoryId: $categoryId ? (int) $categoryId : null,
            productId: $productId ? (int) $productId : null,
            unitId: $unitId,
            filterOnlyAvailable: (bool) $showAvailableInStock,
            productIds: array_map('intval', array_filter($productIds)),
            perPage: (int) $perPage,
            includePrices: true,  // تضمين الأسعار للتقارير
        );

        $service = new OptimizedInventoryService($filter);
        return $service->getInventoryReport();
    }

    /**
     * استخدام الكلاس القديم (للتوافق)
     */
    protected function getLegacyReport($categoryId, $productId, $productIds, $unitId, $storeId, $showAvailableInStock, $perPage): array
    {
        $inventoryService = new MultiProductsInventoryService($categoryId, $productId, $unitId, $storeId, $showAvailableInStock);
        $inventoryService->setProductIds($productIds);
        return $inventoryService->getInventoryReportWithPagination($perPage);
    }
}
