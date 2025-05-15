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

        $reportService = new PurchaseInvoiceProductSummaryReportService();

        $purchased = $reportService->getProductSummaryPerInvoice(); // or with filters
        $ordered = $reportService->getOrderedProductsLinkedToPurchase();

        $diffReport = $reportService->calculatePurchaseVsOrderedDifference($purchased, $ordered);
        return ['reportData' => $diffReport, 'pagination' => null];
    }
}
