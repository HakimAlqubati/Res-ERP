<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionPurchaseReportResource;
use Filament\Resources\Pages\Page;
use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;

class OrderDetails extends Page
{
    protected static string $resource = InventoryTransactionPurchaseReportResource::class;
    protected string $view = 'filament.pages.inventory-reports.order-details';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $title = 'Order Product Details';

    public $reportData = [];

    public function mount($product)
    {
        $filters = ['product_id' => $product, 'details' => 1];
        $service = new PurchaseInvoiceProductSummaryReportService();
        $this->reportData = $service->getOrderedProductsLinkedToPurchase($filters);
    }
}
