<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryWithUsageReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryWithUsageReportResource;
use App\Services\Reports\InventoryWithUsageReportService;
use Filament\Resources\Pages\ListRecords;

class ListInventoryWithUsageReport extends ListRecords
{
    protected static string $resource = InventoryWithUsageReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.inventory-with-usage-report';

    protected function getViewData(): array
    {
        $productId = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $categoryId = $this->getTable()->getFilters()['category_id']->getState()['value'] ?? null;
 
        if (!$storeId) {
            return [ 'storeId' => null, 'reportData' => [], 'pagination' => null ];
        }

        $reportService = new InventoryWithUsageReportService(
            storeId: $storeId,
            categoryId: $categoryId,
            productId: $productId,
        );

        $perPage = request()->get('perPage', 15);
        if ($perPage === 'all') {
            $perPage = 9999;
        }

        $report = $reportService->getReport();
        
        $reportData = $report['reportData'] ?? $report;
        $pagination = $report['pagination'] ?? $report;

        return [
            'reportData' => $reportData,
            'storeId' => $storeId,
            'pagination' => $pagination,
            'final_total_price' => $report['final_total_price'] ?? 0,
            'final_price' => $report['final_price'] ?? 0,
        ];
    }
}
