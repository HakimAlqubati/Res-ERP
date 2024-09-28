<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeRatingReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeRatingReportResource;
use App\Models\Employee;
use Filament\Actions\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ViewDetails extends Page
{ 
    protected static string $resource = EmployeeRatingReportResource::class;

    protected static string $view = 'filament.clusters.h-r-attendance-report.resources.employee-rating-report-resource.pages.view-details';
    protected function getViewData(): array
    {
        $emp_id= $_GET['employee_id'];
        $data = $this->getDetailsData($emp_id);
        $employee_data = Employee::with('branch')->find($emp_id);

        return ['data' => $data, 'employee_data' => $employee_data];
    }

   

  

    public function getDetailsData($assignedTo)
    {
        return DB::table('hr_tasks')
            ->select(
                'hr_tasks.id as task_id',
                'hr_employees.name as employee_name',
                'hr_employees.employee_no as employee_no',
                'hr_task_rating.rating_value as rating_value',
                'hr_task_rating.comment as ratter_comment'
            )
            ->join('hr_employees', 'hr_tasks.assigned_to', '=', 'hr_employees.id')
            ->join('hr_task_rating', 'hr_tasks.id', '=', 'hr_task_rating.task_id')
            ->where('hr_tasks.assigned_to', $assignedTo)
            ->get();
    }
}
