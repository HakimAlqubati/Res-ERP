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
    protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees';
    // protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees-with-header-fixed';

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
        // $query->where('id',6);
        
        $employees = $query->get()->pluck('id')->toArray();

        $report_data = employeeAttendancesByDate($employees, $date);
        $data = array_values($report_data);

// dd($data[0][$date]['employee_name']);
        return [
            'report_data' => $report_data,
            'branch_id' => $branch_id,
            'date' => $date,
        ];
    }
    public function getViewData_backup(): array
    {
        $branch_id = $this->getTable()->getFilters()['branch_id']->getState()['value'];
        $date = $this->getTable()->getFilters()['filter_date']->getState()['date'];

        $report_data = [];
        $work_periods = WorkPeriod::where('active', 1)->select('id',
            'name',
            'start_at',
            'end_at', 'days')->get()->map(function ($period) {
            $period->days = json_decode($period->days);
            return $period;
        })->keyBy('id');

        $day_of_week = date('l', strtotime($date));
        $work_day_periods = [];
        foreach ($work_periods as $key => $period) {
            // Check if the period applies to the given day
            if (in_array($day_of_week, $period->days)) {
                $work_day_periods[$key] = $period;
            }
        }

        $query = Employee::query();
        $employees = $query->select('id', 'name');
        if ($branch_id != '') {
            $employees = $query->where('branch_id', $branch_id);
        }
        $employees = $query->get();

        $report_data = $this->getEmployeeAttendance($employees, $date);

        return [
            'report_data' => $report_data['data'],
            'branch_id' => $branch_id,
            'date' => $date,
            'work_periods' => $work_day_periods,
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
    // public function getEmployeeAttendance($employees, $date)
    // {
    //     // $employee = Employee::find($employee_id);
    //     // $leaveApplications = $employee?->approvedLeaveApplications()
    //     //     ->where(function ($query) use ($start_date, $end_date) {
    //     //         $query->whereBetween('from_date', [$start_date, $end_date])
    //     //             ->orWhereBetween('to_date', [$start_date, $end_date]);
    //     //     })
    //     //     ->select('from_date', 'to_date', 'leave_type_id')
    //     //     ->get();

    //     // Initialize an array to store all leave dates
    //     // $leaveDates = [];
    //     // if ($employee) {
    //     //     foreach ($leaveApplications as $leave) {

    //     //         $fromDate = Carbon::parse($leave->from_date);
    //     //         $toDate = Carbon::parse($leave->to_date);

    //     //         // Create a loop to generate the list of dates
    //     //         for ($date = $fromDate; $date->lte($toDate); $date->addDay()) {
    //     //             // $leaveDates[$date->format('Y-m-d')] = $date->format('Y-m-d'); // Add date to the array
    //     //             $leaveDates[$date->format('Y-m-d')] = 'Leave application approved for (' . $leave?->leaveType?->name . ')'; // Add date to the array
    //     //         }
    //     //     }
    //     // }
    //     // Loop through each leave application and generate dates between 'from_date' and 'to_date'
    //     // if (is_array($leaveApplications) && count($leaveApplications) > 0) {
    //     // }
    //     // dd($leaveApplications, $leaveDates);
    //     $report_data['data'] = [];
    //     foreach ($employees as $employee) {
    //         $report_employees['data'] = [];
    //         $holidays = Holiday::where('active', 1)
    //         // ->whereBetween('from_date', [$start_date, $end_date])
    //         // ->orWhereBetween('to_date', [$start_date, $end_date])
    //             ->whereDate('from_date', '>=', $date)
    //             ->whereDate('to_date', '<=', $date)
    //             ->select('from_date', 'to_date', 'count_days', 'name')
    //             ->get()
    //             ->keyBy('from_date');

    //         $weekend_days = json_decode(WeeklyHoliday::select('days')->first()->days);

    //         // Loop through all dates and check if there is attendance data for each date

    //         $formatted_date = $date;

    //         $day_of_week = date('l', strtotime($date));

    //         $work_periods = WorkPeriod::where('active', 1)->get()->map(function ($period) {
    //             $period->days = json_decode($period->days);
    //             return $period;
    //         });

    //         // Find matching work periods for the given day
    //         $matching_periods = $work_periods->filter(function ($period) use ($day_of_week) {
    //             return in_array($day_of_week, $period->days);
    //         });

    //         // Check if the date is a holiday
    //         foreach ($matching_periods as $matching_period) {

    //             // Fetch attendance data for the employee within the date range
    //             $employee_attendances = DB::table('hr_attendances')
    //                 ->join('hr_employees', 'hr_attendances.employee_id', '=', 'hr_employees.id')
    //                 ->select(
    //                     'hr_attendances.employee_id',
    //                     'hr_employees.employee_no as employee_no',
    //                     'hr_employees.name as employee_name',
    //                     'hr_attendances.check_type',
    //                     'hr_attendances.check_date',
    //                     'hr_attendances.check_time',
    //                     'hr_attendances.day',
    //                     'hr_attendances.supposed_duration_hourly',
    //                     'hr_attendances.actual_duration_hourly',
    //                     'hr_attendances.late_departure_minutes',
    //                     'hr_attendances.early_arrival_minutes',
    //                     'hr_attendances.status',
    //                     'hr_attendances.period_id',
    //                     'hr_attendances.id',

    //                 )
    //                 ->whereDate('hr_attendances.check_date', $date)
    //                 ->where('hr_attendances.employee_id', $employee->id)
    //                 ->where('hr_attendances.period_id', $matching_period->id)
    //                 ->orderBy('hr_attendances.check_date')
    //                 ->get()
    //                 ->groupBy('check_date')
    //             ;

    //             // return $employee_attendances;

    //             // Convert employee attendances to array if it's a collection
    //             $employee_attendances_array = $employee_attendances->toArray();

    //             if (isset($holidays[$formatted_date])) {
    //                 // If the date is a holiday, add it as a holiday

    //                 $holiday = $holidays[$formatted_date];
    //                 $report_data['data'][$formatted_date][$matching_period->id][] = (object) [
    //                     'period_id' => $matching_period->id,
    //                     'employee_id' => $employee->id,
    //                     'employee_no' => 'N/A', // Adjust accordingly
    //                     'employee_name' => 'N/A', // Adjust accordingly
    //                     'check_type' => 'Holiday',
    //                     'check_date' => $formatted_date,
    //                     'check_time' => null,
    //                     'day' => $day_of_week, // Add the day for holidays
    //                     'holiday_name' => 'Holiday of (' . $holiday->name . ')', // Add the holiday name
    //                 ];

    //             } else if (isset($leaveDates[$formatted_date])) {
    //                 // If the date is a approved leave application, add it as a approved leave application
    //                 // dd($leaveDates,array_values($leaveDates));
    //                 $leave_date = $leaveDates[$formatted_date];
    //                 $report_data['data'][$formatted_date][$matching_period->id][] = (object) [
    //                     'period_id' => $matching_period->id,
    //                     'employee_id' => $employee->id,
    //                     'employee_no' => 'N/A', // Adjust accordingly
    //                     'employee_name' => 'N/A', // Adjust accordingly
    //                     'check_type' => 'ApprovedLeaveApplication',
    //                     'check_date' => $formatted_date,
    //                     'check_time' => null,
    //                     'day' => $day_of_week,
    //                     'leave_type_name' => $leave_date,
    //                 ];

    //             } else {

    //                 // Filter attendances for the current date
    //                 $attendances_for_date = array_filter($employee_attendances_array, function ($attendances) use ($formatted_date) {
    //                     // Each attendance date holds an array of attendances (e.g., "checkin" and "checkout")
    //                     foreach ($attendances as $attendance) {
    //                         // Check if one of the attendance entries matches the date
    //                         if ($attendance->check_date === $formatted_date) {
    //                             return true;
    //                         }
    //                     }
    //                     return false;
    //                 });
    //                 if (!empty($attendances_for_date)) {
    //                     // Loop through all the attendances for the date
    //                     foreach ($attendances_for_date as $attendances) {
    //                         foreach ($attendances as $attendance) {
    //                             if ($attendance->period_id == $matching_period->id) {
    //                                 $report_data['data'][$formatted_date][$matching_period->id][] = (object) [
    //                                     'employee_id' => $attendance->employee_id,
    //                                     'employee_no' => $attendance->employee_no,
    //                                     'employee_name' => $attendance->employee_name,
    //                                     'check_type' => $attendance->check_type,
    //                                     'check_date' => $attendance->check_date,
    //                                     'check_time' => $attendance->check_time,
    //                                     'day' => $attendance->day,
    //                                     'actual_duration_hourly' => $attendance->actual_duration_hourly,
    //                                     'supposed_duration_hourly' => $attendance->supposed_duration_hourly,
    //                                     'early_arrival_minutes' => $attendance->early_arrival_minutes,
    //                                     'late_departure_minutes' => $attendance->late_departure_minutes,
    //                                     'status' => $attendance->status,
    //                                     'period_id' => $matching_period->id,
    //                                     'period_start_at' => $matching_period->start_at,
    //                                     'period_end_at' => $matching_period->end_at,
    //                                     'id' => $attendance->id,
    //                                 ];
    //                             }
    //                         }
    //                     }
    //                 } else {
    //                     // Check if the day is a weekend
    //                     if (in_array($day_of_week, $weekend_days)) {
    //                         // Add a row with 'Weekend' status for weekend days
    //                         $report_data['data'][$formatted_date][] = (object) [
    //                             'employee_id' => $employee->id,
    //                             'employee_no' => 'N/A', // Adjust accordingly
    //                             'employee_name' => 'N/A', // Adjust accordingly
    //                             'check_type' => 'Weekend',
    //                             'check_date' => $formatted_date,
    //                             'check_time' => null,
    //                             'day' => $day_of_week, // Add the day for weekend
    //                         ];
    //                     } else {
    //                         // Add a row with 'Absent' status for missing dates that are not weekends or holidays
    //                         $report_data['data'][$formatted_date][$matching_period->id][] = (object) [
    //                             'period_id' => $matching_period->id,
    //                             'employee_id' => $employee->id,
    //                             'employee_no' => 'N/A', // Adjust accordingly
    //                             'employee_name' => 'N/A', // Adjust accordingly
    //                             'check_type' => 'Absent',
    //                             'period_start_at' => $matching_period->start_at,
    //                             'period_end_at' => $matching_period->end_at,
    //                             'check_date' => $formatted_date,
    //                             'check_time' => null,
    //                             'day' => $day_of_week, // Add the day for absent days
    //                         ];
    //                     }
    //                 }

    //             }
    //         }
    //         $report_employees['employees'] = $report_data;
    //     }

    //     return $report_employees;
    // }

}
