<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionTruckingReportResource;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactionTruckingReport extends ListRecords
{
    use HasBackButtonAction;
    protected static string $resource = InventoryTransactionTruckingReportResource::class;
    protected string $view = 'filament.pages.inventory-reports.inventory-trucking-report';
    public $perPage = 50;
    protected function getViewData(): array
    {
        $productId = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;
        $movementType = $this->getTable()->getFilters()['movement_type']->getState()['value'] ?? null;
        $unitId = $this->getTable()->getFilters()['unit_id']->getState()['value'] ?? null;
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $transactionableType = $this->getTable()->getFilters()['transactionable_type']->getState()['value'] ?? null;

        $product = Product::find($productId);

        $reportData = collect();


        $perPage = $this->perPage;

        if ($perPage === 'all') {
            $perPage = 9999; // أو أي عدد كبير جدًا لضمان عرض الكل
        }


        if (!empty($productId)) {
            $rawData = InventoryTransaction::getInventoryTrackingDataPagination(
                $productId,
                $perPage,
                $movementType,
                $unitId,
                $storeId,
                $transactionableType
            );
            $reportData = $rawData->through(function ($item) {
                $item->formatted_transactionable_type = class_basename($item->transactionable_type);
                $item->batch_number = $item->movement_date ? \Carbon\Carbon::parse($item->movement_date)->format('Ymd') : '';
                return $item;
            });
        }
        return ['reportData' => $reportData, 'product' => $product, 'unitId' => $unitId, 'movementType' => $movementType];
    }
}
