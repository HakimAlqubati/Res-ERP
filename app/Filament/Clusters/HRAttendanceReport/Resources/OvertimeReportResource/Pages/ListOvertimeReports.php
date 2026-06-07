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
        $filters = $this->getTable()->getFilters();
        
        $branchId   = $filters['branch_id']->getState()['value'] ?? null;
        $employeeId = $filters['employee_id']->getState()['value'] ?? null;
        $dateFrom   = $filters['date_range']->getState()['date_from'] ?? null;
        $dateTo     = $filters['date_range']->getState()['date_to'] ?? null;
        $status     = $filters['status']->getState()['value'] ?? null;

        // Get labels for header
        $branchName = '-';
        if ($branchId) {
            $branchName = \App\Models\Branch::find($branchId)?->name ?? '-';
        }

        $employee = null;
        if ($employeeId) {
            $employee = \App\Models\Employee::find($employeeId);
        }

        $filter = new OvertimeReportFilter(
            branchId: $branchId ? (int) $branchId : null,
            employeeId: $employeeId ? (int) $employeeId : null,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            status: $status !== null && $status !== '' ? (string) $status : null,
            page: (int) $this->getTablePage()
        );

        $report = app(OvertimeReportService::class)->generate($filter);

        return [
            'items'        => $report['items'],
            'summary'      => $report['summary'],
            'branch_name'  => $branchName,
            'branch_id'    => $branchId,
            'employee'     => $employee,
            'start_date'   => $dateFrom ?? '-',
            'end_date'     => $dateTo ?? '-',
            'employee_id'  => $employeeId,
        ];
    }
}
