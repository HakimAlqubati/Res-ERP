<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionPurchaseReportResource;
use Filament\Resources\Pages\Page;
use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;

class PurchaseDetails extends Page
{
    protected static string $resource = InventoryTransactionPurchaseReportResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string $view = 'filament.pages.inventory-reports.purchase-details';
    protected static ?string $title = 'Purchased Product Details';

    public $reportData = [];

    public function mount($product)
    {
        $filters = ['product_id' => $product, 'details' => 1];
        $service = new PurchaseInvoiceProductSummaryReportService();
        $this->reportData = $service->getProductSummaryPerInvoice($filters, true);
    }
}
