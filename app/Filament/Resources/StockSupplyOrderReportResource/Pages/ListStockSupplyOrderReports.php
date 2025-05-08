<?php

namespace App\Filament\Resources\StockSupplyOrderReportResource\Pages;

use App\Filament\Resources\StockSupplyOrderReportResource;
use App\Models\Store;
use App\Services\StockSupply\Reports\StockSupplyOrderReportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockSupplyOrderReports extends ListRecords
{
    protected static string $resource = StockSupplyOrderReportResource::class;
    protected static string $view = 'filament.pages.stock-supply-order-reports.stock-supply-order-report';


    protected function getViewData(): array
    {
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $startDate = $this->getTable()->getFilters()['date_range']->getState()['start_date'];
        $enddate = $this->getTable()->getFilters()['date_range']->getState()['end_date'];


        $service = new StockSupplyOrderReportService();
        $data = $service->generateReport($storeId, $startDate, $enddate);
        $store = Store::find($storeId)?->name ?? null;
        return [
            'reportData' => $data,
            'store' => $store,

        ];
    }
}
