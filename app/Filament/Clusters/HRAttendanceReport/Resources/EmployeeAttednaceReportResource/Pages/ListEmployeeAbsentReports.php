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
            ?? $this->getTable()->getFilter('branch_id')->getState()['value']
            ?? null;

        // Handle date filter which might be structured differently depending on Filament version or setup
        $dateFilterState = $this->getTable()->getFilters()['filter_date']->getState();
        $date = $dateFilterState['date'] ?? date('Y-m-d');

        $data = collect([]);

        if ($branchId) {
            $filters = ['branch_id' => $branchId];

            // if (isset($dateFilterState['current_time'])) {
            //     $filters['current_time'] = $dateFilterState['current_time'];
            // }
            $filters['current_time'] = now()->timezone('Asia/Kuala_Lumpur')->format('H:i');

            // dd($filters);
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
