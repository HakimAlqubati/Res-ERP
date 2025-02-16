<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionTruckingReportResource;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactionTruckingReport extends ListRecords
{
    protected static string $resource = InventoryTransactionTruckingReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.inventory-trucking-report';

    protected function getViewData(): array
    {
        $productId = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;

        $product = Product::find($productId);

        $reportData = [];
        if (isset($productId) && $productId != '') {
            $reportData = InventoryTransaction::getInventoryTrackingDataPagination($productId, 15);
        }

        return ['reportData' => $reportData, 'product' => $product];
    }
}
