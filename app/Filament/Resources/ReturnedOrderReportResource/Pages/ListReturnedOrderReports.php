<?php

namespace App\Filament\Resources\ReturnedOrderReportResource\Pages;

use App\Filament\Resources\ReturnedOrderReportResource;
use App\Models\Branch;
use App\Services\Orders\Reports\ReturnedOrdersReportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnedOrderReports extends ListRecords
{
    protected static string $resource = ReturnedOrderReportResource::class;
    protected static string $view = 'filament.pages.order-reports.returned-orders-report';
    public function getViewData(): array
    {
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value'] ?? null;

        $startDate = $this->getTable()->getFilters()['date']->getState()['start_date'];
        $endDate = $this->getTable()->getFilters()['date']->getState()['end_date'];
        $filters = [
            'branch_id' => $branchId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        $data = (new ReturnedOrdersReportService())->generate($filters);
        
        return [
            'reportData' => $data, 
            'start_date' => $startDate,
            'end_date' => $endDate, 
        ];
    }
}
