<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionPurchaseReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;
use App\Http\Controllers\TestController5;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\MultiProductsInventoryPurchasedService;
use App\Services\MultiProductsInventoryService;
use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactionPurchaseReport extends ListRecords
{
    protected static string $resource = InventoryTransactionPurchaseReportResource::class;
    // protected static string $view = 'filament.pages.inventory-reports.inventory-report';
    protected static string $view = 'filament.pages.inventory-reports.multi-products-inventory-purchase-report';

    protected function getViewData(): array
    {
        $productId = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;

        $reportService = new PurchaseInvoiceProductSummaryReportService();
        $filters = ['product_id' => $productId, 'group_by_invoice' => 1];
        $purchased = $reportService->getProductSummaryPerInvoice($filters); // or with filters
        $ordered = $reportService->getOrderedProductsFromExcelImport($filters);

        $diffReport = $reportService->calculatePurchaseVsOrderedDifference($purchased, $ordered);
        return ['reportData' => $diffReport, 'pagination' => null];
    }
}
