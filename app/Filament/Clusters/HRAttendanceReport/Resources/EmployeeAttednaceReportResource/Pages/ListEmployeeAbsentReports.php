<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAbsentsReportResource;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeAbsentReports extends ListRecords
{
    protected static string $resource = EmployeeAbsentsReportResource::class;



    protected static string $view = 'filament.pages.hr-reports.attendance.pages.absent-employees';
    protected function getViewData(): array
    {
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value'];
        $date = $this->getTable()->getFilters()['filter_date']->getState()['date'];
        $currentTime = $this->getTable()->getFilters()['filter_date']->getState()['current_time'];

        // $report_data = $this->getReportData2($employee_id, $start_date, $end_date);
        $data = reportAbsentEmployees($date, $branchId, $currentTime);
    
        return [
            'report_data' => $data,
            'branch_id' => $branchId,
            'date' => $date,

        ];
    }
}