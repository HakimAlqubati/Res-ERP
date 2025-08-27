<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\AbsenceTrackingReportResource;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
use App\Http\Controllers\TestController3;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\WeeklyHoliday;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListAbsenceTrackingReports extends ListRecords
{
    protected static string $resource = AbsenceTrackingReportResource::class;



    protected string $view = 'filament.pages.hr-reports.attendance.pages.attendance-tracking-employee';
    protected function getViewData(): array
    {

        $starDate = $this->getTable()->getFilters()['date_range']->getState()['start_date'];
        $endDate = $this->getTable()->getFilters()['date_range']->getState()['end_date'];
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value'];
        $data = (new TestController3())->getEmployeesWithOddAttendances($starDate, $endDate,$branchId) ?? [];
        return [
            'report_data' => $data,
            'start_date' => $starDate,
            'end_date' => $endDate,
            'branch_id'=>$branchId,

        ];
    }
}
