<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MissingInventoryProductsReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MissingInventoryProductsReportResource;
use App\Models\Store;
use App\Services\StockInventoryReportService;
use Filament\Resources\Pages\ListRecords;

class ListMissingInventoryProductsReport extends ListRecords
{
    protected static string $resource = MissingInventoryProductsReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.missing-inventory-products';
    protected function getHeaderActions(): array
    {
        return [];
    }
    protected function getViewData(): array
    {
        $perPage = request()->get('perPage', 20);
        $start = $this->getTable()->getFilters()['date_range']->getState()['start_date'];
        $end = $this->getTable()->getFilters()['date_range']->getState()['end_date'];

        if ($perPage === 'all') {
            $perPage = 9999; // سيتم إرجاع كل النتائج
        }
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? 'all';
        $products = StockInventoryReportService::getProductsNotInventoriedBetween($start, $end, $perPage, $storeId);
        $store = Store::find($storeId)?->name ?? 'All Stores';

        return [
            'reportData' => $products,
            'startDate' => $start,
            'endDate' => $end,
            'store' => $store,
        ];
    }
}
