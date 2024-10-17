<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeesAttednaceReportResource;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\WeeklyHoliday;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListEmployeesAttednaceReport extends ListRecords
{
    protected static string $resource = EmployeesAttednaceReportResource::class;
    // protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees';
    protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees-with-header-fixed';

    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();

        return $attributes['employee_id'];
    }

    public function getViewData(): array
    {
        $branch_id = $this->getTable()->getFilters()['branch_id']->getState()['value'];
        $date = $this->getTable()->getFilters()['filter_date']->getState()['date'];

        $report_data = [];

        $query = Employee::query();
        $employees = $query->select('id');
        if ($branch_id != '') {
            $employees = $query->where('branch_id', $branch_id);
        }
        
        
        $employees = $query->get()->pluck('id')->toArray();

        $report_data = employeeAttendancesByDate($employees, $date);
 
        return [
            'report_data' => $report_data,
            'branch_id' => $branch_id,
            'date' => $date,
        ];
    }
    

    public function getEmployeeAttendance($employees, $date)
    {
        // Initialize an array to store report data for all employees
        $report_data = ['data' => []];

        // Fetch active work periods and decode the days array
        $work_periods = WorkPeriod::where('active', 1)->get()->map(function ($period) {
            $period->days = json_decode($period->days);
            return $period;
        });

        // Get weekend days from the settings
        $weekend_days = json_decode(WeeklyHoliday::select('days')->first()->days);

        // Fetch holidays for the given date
        $holidays = Holiday::where('active', 1)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->select('from_date', 'to_date', 'count_days', 'name')
            ->get()
            ->keyBy('from_date');

        $formatted_date = $date;
        $day_of_week = date('l', strtotime($date));

        // Loop through each employee
        foreach ($employees as $employee) {
            // Fetch leave applications for the employee (if any)
            $leaveDates = $this->getEmployeeLeaveDates($employee, $formatted_date);

            // Loop through each work period
            foreach ($work_periods as $period) {
                // Check if the period applies to the given day
                if (in_array($day_of_week, $period->days)) {

                    // Fetch attendance data for the employee on the given date and period
                    $attendances = DB::table('hr_attendances')
                        ->join('hr_employees', 'hr_attendances.employee_id', '=', 'hr_employees.id')
                        ->select(
                            'hr_attendances.employee_id',
                            'hr_employees.employee_no as employee_no',
                            'hr_employees.name as employee_name',
                            'hr_attendances.check_type',
                            'hr_attendances.check_date',
                            'hr_attendances.check_time',
                            'hr_attendances.day',
                            'hr_attendances.supposed_duration_hourly',
                            'hr_attendances.actual_duration_hourly',
                            'hr_attendances.late_departure_minutes',
                            'hr_attendances.early_arrival_minutes',
                            'hr_attendances.status',
                            'hr_attendances.period_id',
                            'hr_attendances.id'
                        )
                        ->whereDate('hr_attendances.check_date', $formatted_date)
                        ->where('hr_attendances.employee_id', $employee->id)
                        ->where('hr_attendances.period_id', $period->id)
                        ->orderBy('hr_attendances.check_date')
                        ->get();

                    if (isset($holidays[$formatted_date])) {
                        // If the date is a holiday, add it as a holiday
                        $holiday = $holidays[$formatted_date];
                        $report_data['data'][$employee->name][$period->id][] = (object) [
                            'period_id' => $period->id,
                            'employee_id' => $employee->id,
                            'employee_no' => 'N/A',
                            'employee_name' => $employee->name,
                            'check_type' => 'Holiday',
                            'check_date' => $formatted_date,
                            'check_time' => null,
                            'day' => $day_of_week,
                            'holiday_name' => 'Holiday of (' . $holiday->name . ')',
                        ];
                    } elseif (isset($leaveDates[$formatted_date])) {
                        // If the date is a leave, add it as a leave
                        $leave_date = $leaveDates[$formatted_date];
                        $report_data['data'][$employee->name][$period->id][] = (object) [
                            'period_id' => $period->id,
                            'employee_id' => $employee->id,
                            'employee_no' => 'N/A',
                            'employee_name' => $employee->name,
                            'check_type' => 'ApprovedLeaveApplication',
                            'check_date' => $formatted_date,
                            'check_time' => null,
                            'day' => $day_of_week,
                            'leave_type_name' => $leave_date,
                        ];
                    } elseif ($attendances->isNotEmpty()) {
                        // If there are attendance records, add them
                        foreach ($attendances as $attendance) {
                            $report_data['data'][$employee->name][$period->id][] = (object) [
                                'employee_id' => $attendance->employee_id,
                                'employee_no' => $attendance->employee_no,
                                'employee_name' => $attendance->employee_name,
                                'check_type' => $attendance->check_type,
                                'check_date' => $attendance->check_date,
                                'check_time' => $attendance->check_time,
                                'day' => $attendance->day,
                                'actual_duration_hourly' => $attendance->actual_duration_hourly,
                                'supposed_duration_hourly' => $attendance->supposed_duration_hourly,
                                'early_arrival_minutes' => $attendance->early_arrival_minutes,
                                'late_departure_minutes' => $attendance->late_departure_minutes,
                                'status' => $attendance->status,
                                'period_id' => $period->id,
                                'period_start_at' => $period->start_at,
                                'period_end_at' => $period->end_at,
                                'id' => $attendance->id,
                            ];
                        }
                    } else {
                        // If no attendance, check if it's a weekend
                        if (in_array($day_of_week, $weekend_days)) {
                            $report_data['data'][$employee->name][$period->id][] = (object) [
                                'employee_id' => $employee->id,
                                'employee_no' => 'N/A',
                                'employee_name' => $employee->name,
                                'check_type' => 'Weekend',
                                'check_date' => $formatted_date,
                                'check_time' => null,
                                'day' => $day_of_week,
                            ];
                        } else {
                            // Otherwise, mark as absent
                            $report_data['data'][$employee->name][$period->id][] = (object) [
                                'employee_id' => $employee->id,
                                'employee_no' => 'N/A',
                                'employee_name' => $employee->name,
                                'check_type' => 'Absent',
                                'period_id' => $period->id,
                                'period_start_at' => $period->start_at,
                                'period_end_at' => $period->end_at,
                                'check_date' => $formatted_date,
                                'check_time' => null,
                                'day' => $day_of_week,
                            ];
                        }
                    }
                }
            }
        }

        return $report_data;
    }

    // Helper function to get employee leave dates (if any)
    private function getEmployeeLeaveDates($employee, $formatted_date)
    {
        $leaveApplications = $employee->approvedLeaveApplications()
            ->select('from_date', 'to_date', 'leave_type_id')
            ->get();

        $leaveDates = [];
        foreach ($leaveApplications as $leave) {
            $fromDate = Carbon::parse($leave->from_date);
            $toDate = Carbon::parse($leave->to_date);

            for ($date = $fromDate; $date->lte($toDate); $date->addDay()) {
                $leaveDates[$date->format('Y-m-d')] = 'Leave application approved for (' . $leave->leaveType->name . ')';
            }
        }

        return $leaveDates;
    }

    public function showDetails($date, $employeeId, $periodId)
    {
        $AttendanceDetails = getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);
        dd($AttendanceDetails,$periodId,$date,$employeeId);
        return dd($AttendanceDetails->toArray());
    }
}
