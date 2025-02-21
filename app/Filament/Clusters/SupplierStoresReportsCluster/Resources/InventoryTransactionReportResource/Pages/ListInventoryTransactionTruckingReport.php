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

        $reportData = collect();

        if (!empty($productId)) {
            $rawData = InventoryTransaction::getInventoryTrackingDataPagination($productId, 15);
            $reportData = $rawData->through(function ($item) {
                $item->formatted_transactionable_type = class_basename($item->transactionable_type);
                return $item;
            });
        }
        return ['reportData' => $reportData, 'product' => $product];
    }
}
