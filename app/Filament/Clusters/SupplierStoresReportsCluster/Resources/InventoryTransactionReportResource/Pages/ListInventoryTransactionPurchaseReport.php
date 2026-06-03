<?php
namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionPurchaseReportResource;
use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactionPurchaseReport extends ListRecords
{
    use HasBackButtonAction;
    protected static string $resource = InventoryTransactionPurchaseReportResource::class;
    // protected static string $view = 'filament.pages.inventory-reports.inventory-report';
    protected string $view = 'filament.pages.inventory-reports.multi-products-inventory-purchase-report';

    protected function getViewData(): array
    {
        $productId   = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;
        $storeId     = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $productType = $this->getTable()->getFilters()['manufacturing_filter']->getState()['value'] ?? null;
        $categoryId  = $this->getTable()->getFilters()['category_id']->getState()['value'] ?? null;

        $reportService = new PurchaseInvoiceProductSummaryReportService();
        $filters       = [
            'product_id'       => $productId,
            'group_by_invoice' => 1,
            'store_id'         => $storeId,

        ];
        if ($productType === 'only_mana') {
            $filters['only_manufacturing'] = 1;
        } elseif ($productType === 'only_unmana') {
            $filters['only_unmanufacturing'] = 1;
        }
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        $purchased = $reportService->getProductSummaryPerInvoice($filters); // or with filters
        $ordered   = $reportService->getOrderedProductsLinkedToPurchase($filters);

        $diffReport = $reportService->calculatePurchaseVsOrderedDifference($purchased, $ordered, $storeId);
        return ['reportData' => $diffReport, 'storeId' => $storeId, 'pagination' => null];
    }
}