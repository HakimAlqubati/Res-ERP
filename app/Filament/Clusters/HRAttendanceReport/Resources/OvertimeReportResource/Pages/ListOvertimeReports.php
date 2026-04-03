<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\OvertimeReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\OvertimeReportResource;
use App\Modules\HR\Overtime\Reports\DTOs\OvertimeReportFilter;
use App\Modules\HR\Overtime\Reports\OvertimeReportService;
use Filament\Resources\Pages\ListRecords;

class ListOvertimeReports extends ListRecords
{
    protected string $view = 'filament.pages.hr-reports.overtime.pages.overtime-report';
    protected static string $resource = OvertimeReportResource::class;

    protected function getViewData(): array
    {
        $branchId   = $this->getTable()->getFilters()['branch_id']->getState()['value'] ?? null;
        $employeeId = $this->getTable()->getFilters()['employee_id']->getState()['value'] ?? null;
        $dateFrom   = $this->getTable()->getFilters()['date_range']->getState()['date_from'] ?? null;
        $dateTo     = $this->getTable()->getFilters()['date_range']->getState()['date_to'] ?? null;
        $status = $this->getTable()->getFilters()['status']->getState()['value'] ?? null;

        $filter = new OvertimeReportFilter(
            branchId: $branchId ? (int) $branchId : null,
            employeeId: $employeeId ? (int) $employeeId : null,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            status: $status !== null && $status !== '' ? (string) $status : null,
        );

        $report = app(OvertimeReportService::class)->generate($filter);

        return [
            'items'   => $report['items'],
            'summary' => $report['summary'],
        ];
    }
}
