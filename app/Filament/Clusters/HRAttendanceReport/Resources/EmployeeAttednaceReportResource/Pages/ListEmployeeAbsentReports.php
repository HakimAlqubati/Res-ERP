<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAbsentsReportResource;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeAbsentReports extends ListRecords
{
    protected static string $resource = EmployeeAbsentsReportResource::class;



    protected string $view = 'filament.pages.hr-reports.attendance.pages.absent-employees';
    protected function getViewData(): array
    {
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value']
            ?? current($this->getTable()->getFilter('branch_id')->getState() ?: [])
            ?? null;

        $dateFilterState = $this->getTable()->getFilters()['date_range']?->getState() ?? [];
        $type = $dateFilterState['type'] ?? 'single';

        if ($type === 'single') {
            $dateFrom = $dateFilterState['date'] ?? now()->format('Y-m-d');
            $dateTo = $dateFrom;
        } else {
            $dateFrom = $dateFilterState['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
            $dateTo = $dateFilterState['end_date'] ?? now()->format('Y-m-d');
        }

        $data = collect([]);

        if ($branchId) {
            $filters = ['branch_id' => $branchId];

            $today = now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d');

            if ($dateFrom === $today && $dateTo === $today) {
                // If checking today's absentees, only show those whose shift has already started
                $filters['current_time'] = now()->timezone('Asia/Kuala_Lumpur')->format('H:i');
            } elseif ($dateFrom > $today || $dateTo > $today) {
                // Future dates shouldn't show absentees, pass a very early time to prevent match
                $filters['current_time'] = '00:00';
            }
            // For past dates, we do NOT set current_time, so all absent shifts are included.

            /** @var \App\Services\HR\AttendanceHelpers\Reports\AbsentEmployeesV2Service $service */
            $service = app(\App\Services\HR\AttendanceHelpers\Reports\AbsentEmployeesV2Service::class);
            $data = $service->getAbsentEmployees($dateFrom, $dateTo, $filters);
        }

        return [
            'report_data'   => $data,
            'branch_id'     => $branchId,
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
        ];
    }
}
