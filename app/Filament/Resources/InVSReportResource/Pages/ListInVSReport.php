<?php

namespace App\Filament\Resources\InVSReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Resources\InVSReportResource;
use App\Models\Store;
use App\Services\Reports\CenteralKitchens\InVsOutReportService;
use App\Services\StockSupply\Reports\StockSupplyOrderReportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInVSReport extends ListRecords
{
    use HasBackButtonAction;
    protected static string $resource = InVSReportResource::class;
    protected string $view = 'filament.pages.stock-report.in-vs-out-report';


    protected function getViewData(): array
    {
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;

        // $toDate = $this->getTable()->getFilters()['date']->getState()['to_date'];
        $dateState = $this->getTable()->getFilters()['date_range']->getState();

        $fromDate = $dateState['from_date'] ?? null;
        $toDate   = $dateState['to_date'] ?? null;

        $filters = [
            'store_id'  => $storeId,
            'from_date' => $fromDate,
            'to_date'   => $toDate,
        ];

        // $filters = [
        //     'store_id' => $storeId,
        //     'to_date' => $toDate,
        // ];


        $reportService = new InVsOutReportService();

        $data = $reportService->getFinalComparison($filters);

        $store = Store::find($storeId)?->name ?? null;
        return [
            'reportData' => $data,
            'store' => $store,
            'toDate' => $toDate,

        ];
    }
}
