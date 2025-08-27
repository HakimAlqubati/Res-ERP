<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MissingInventoryProductsReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MissingInventoryProductsReportResource;
use App\Models\Store;
use App\Services\StockInventoryReportService;
use Filament\Resources\Pages\ListRecords;

class ListMissingInventoryProductsReport extends ListRecords
{
    protected static string $resource = MissingInventoryProductsReportResource::class;
    protected string $view = 'filament.pages.inventory-reports.missing-inventory-products';
    protected function getHeaderActions(): array
    {
        return [];
    }
    public $perPage = 15;
    protected function getViewData(): array
    { 
        $start = $this->getTable()->getFilters()['date_range']->getState()['start_date'];
        $end = $this->getTable()->getFilters()['date_range']->getState()['end_date'];
        $hideZero = $this->getTable()->getFilters()['options']->getState()['hide_zero'];
        $perPage = $this->perPage;

        if ($perPage === 'all') {
            $perPage = 9999; // أو أي عدد كبير جدًا لضمان عرض الكل
        }
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? 'all';

        $products = StockInventoryReportService::getProductsNotInventoriedBetween($start, $end, $perPage, $storeId, $hideZero);
        $store = Store::find($storeId)?->name ?? 'All Stores';

        return [
            'reportData' => $products,
            'startDate' => $start,
            'endDate' => $end,
            'store' => $store,
            'storeId' => $storeId,
        ];
    }
}