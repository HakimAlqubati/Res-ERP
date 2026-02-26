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

        // Handle date filter which might be structured differently depending on Filament version or setup
        $dateFilterState = $this->getTable()->getFilters()['filter_date']?->getState() ?? [];
        $date = $dateFilterState['date'] ?? date('Y-m-d');

        $data = collect([]);

        if ($branchId) {
            $filters = ['branch_id' => $branchId];

            $today = now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d');

            if ($date === $today) {
                // If checking today's absentees, only show those whose shift has already started
                $filters['current_time'] = now()->timezone('Asia/Kuala_Lumpur')->format('H:i');
            } elseif ($date > $today) {
                // Future dates shouldn't show absentees, pass a very early time to prevent match
                $filters['current_time'] = '00:00';
            }
            // For past dates, we do NOT set current_time, so all absent shifts are included.

            /** @var \App\Services\HR\AttendanceHelpers\Reports\AbsentEmployeesService $service */
            $service = app(\App\Services\HR\AttendanceHelpers\Reports\AbsentEmployeesService::class);
            $data = $service->getAbsentEmployees($date, $filters);
        }

        return [
            'report_data' => $data,
            'branch_id'   => $branchId,
            'date'        => $date,
        ];
    }
}
