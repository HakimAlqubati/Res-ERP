<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
use App\Models\Holiday;
use App\Models\WeeklyHoliday;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListEmployeeAttednaceReports extends ListRecords
{
    protected static string $resource = EmployeeAttednaceReportResource::class;

    protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employee';
    protected function getViewData(): array
    {
        // $updates = request()->input('components.0.updates', []);
        // $start_date = $updates['tableFilters.date_range.start_date'] ?? null;
        // $employee_id = $updates['tableFilters.employee_id.value'] ?? null;
        // $end_date = $updates['tableFilters.date_range.end_date'] ?? null;

        $employee_id = 15;
        $start_date = '2024-09-08';
        $end_date = '2024-09-13';

        $report_data = $this->getReportData_v2($employee_id, $start_date, $end_date);
// dd($report_data);
        return [
            'report_data' => $report_data['data'],
            'employee_id' => $employee_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];
    }
    public function getReportData_v2($employee_id, $start_date, $end_date)
    {
        $report_data['data'] = [];

        $period = Carbon::parse($start_date)->toPeriod($end_date);

        foreach ($period as $date) {
            $formatted_date = $date->format('Y-m-d');
            $day_of_week = $date->format('l');

            $employee_attendances = DB::table('hr_attendances')
                ->select(
                    'hr_attendances.check_type',
                    'hr_attendances.check_date',
                    'hr_attendances.check_time',
                    'hr_attendances.day'
                )
                ->where('hr_attendances.check_date', $date)
                ->where('hr_attendances.employee_id', $employee_id)
                ->orderBy('hr_attendances.check_date')
                ->get()
                ->groupBy('check_date');
            $report_data['data'][$formatted_date][] = (object) [
                
                'check_type' => 'Temp',
                'check_date' => $formatted_date,
                'check_time' => null,
                'day' => $day_of_week,
                'attendances' => $employee_attendances,
            ];

        }
        dd($report_data);
        return $report_data;
        $report_data = [
            'data' => $employee_attendaces,
            'employee_id' => 1,
        ];
        return $report_data;
    }
    public function getReportData($employee_id, $start_date, $end_date)
    {
        // $updates = request()->input('components.0.updates', []);
        // $start_date = $updates['tableFilters.date_range.start_date'] ?? null;
        // $end_date = $updates['tableFilters.date_range.end_date'] ?? null;
        // $employee_id = $updates['tableFilters.employee_Id.value'] ?? null;

        $report_data['data'] = [];

        // $work_periods = WorkPeriod::where('active',1)->select('name','start_at','end_at','allowed_count_minutes_late','days')->get()->toArray();

        // Fetch work periods
        $work_periods = WorkPeriod::where('active', 1)
            ->select('name', 'start_at', 'end_at', 'allowed_count_minutes_late', 'days')
            ->get()
            ->map(function ($period) {
                $period->days = json_decode($period->days); // Decode the days from JSON
                return $period;
            });

        $holidays = Holiday::where('active', 1)
            ->whereBetween('from_date', [$start_date, $end_date])
            ->orWhereBetween('to_date', [$start_date, $end_date])
            ->select('from_date', 'to_date', 'count_days', 'name')
            ->get()
            ->keyBy('from_date');

        $weekend_days = json_decode(WeeklyHoliday::select('days')->first()->days);

        // Fetch attendance data for the employee within the date range
        $employee_attendances = DB::table('hr_attendances')
            ->join('hr_employees', 'hr_attendances.employee_id', '=', 'hr_employees.id')
            ->select(
                'hr_attendances.employee_id',
                'hr_employees.employee_no as employee_no',
                'hr_employees.name as employee_name',
                'hr_attendances.check_type',
                'hr_attendances.check_date',
                'hr_attendances.check_time',
                'hr_attendances.day'
            )
            ->whereBetween('hr_attendances.check_date', [$start_date, $end_date])
            ->where('hr_attendances.employee_id', $employee_id)
            ->orderBy('hr_attendances.check_date')
            ->get()
            ->groupBy('check_date');

        // Convert employee attendances to array if it's a collection
        $employee_attendances_array = $employee_attendances->toArray();

        $period = Carbon::parse($start_date)->toPeriod($end_date);

        // Loop through all dates and check if there is attendance data for each date
        foreach ($period as $date) {
            $formatted_date = $date->format('Y-m-d');
            $day_of_week = $date->format('l'); // Get the day name (e.g., "Saturday")

            // Check if the date is a holiday
            if (isset($holidays[$formatted_date])) {
                // If the date is a holiday, add it as a holiday
                $holiday = $holidays[$formatted_date];
                $report_data['data'][$formatted_date][] = (object) [
                    'employee_id' => $employee_id,
                    'employee_no' => 'N/A', // Adjust accordingly
                    'employee_name' => 'N/A', // Adjust accordingly
                    'check_type' => 'Holiday',
                    'check_date' => $formatted_date,
                    'check_time' => null,
                    'day' => $day_of_week, // Add the day for holidays
                    'holiday_name' => $holiday->name, // Add the holiday name
                ];
            } else {
                // Filter attendances for the current date
                $attendances_for_date = array_filter($employee_attendances_array, function ($attendances) use ($formatted_date) {
                    // Each attendance date holds an array of attendances (e.g., "checkin" and "checkout")
                    foreach ($attendances as $attendance) {
                        // Check if one of the attendance entries matches the date
                        if ($attendance->check_date === $formatted_date) {
                            return true;
                        }
                    }
                    return false;
                });

                if (!empty($attendances_for_date)) {
                    // Loop through all the attendances for the date
                    foreach ($attendances_for_date as $attendances) {
                        foreach ($attendances as $attendance) {
                            $report_data['data'][$formatted_date][] = (object) [
                                'employee_id' => $attendance->employee_id,
                                'employee_no' => $attendance->employee_no,
                                'employee_name' => $attendance->employee_name,
                                'check_type' => $attendance->check_type,
                                'check_date' => $attendance->check_date,
                                'check_time' => $attendance->check_time,
                                'day' => $attendance->day, // Include the day from attendance
                            ];
                        }
                    }
                } else {
                    // Check if the day is a weekend
                    if (in_array($day_of_week, $weekend_days)) {
                        // Add a row with 'Weekend' status for weekend days
                        $report_data['data'][$formatted_date][] = (object) [
                            'employee_id' => $employee_id,
                            'employee_no' => 'N/A', // Adjust accordingly
                            'employee_name' => 'N/A', // Adjust accordingly
                            'check_type' => 'Weekend',
                            'check_date' => $formatted_date,
                            'check_time' => null,
                            'day' => $day_of_week, // Add the day for weekend
                        ];
                    } else {
                        // Add a row with 'Absent' status for missing dates that are not weekends or holidays
                        $report_data['data'][$formatted_date][] = (object) [
                            'employee_id' => $employee_id,
                            'employee_no' => 'N/A', // Adjust accordingly
                            'employee_name' => 'N/A', // Adjust accordingly
                            'check_type' => 'Absent',
                            'check_date' => $formatted_date,
                            'check_time' => null,
                            'day' => $day_of_week, // Add the day for absent days
                        ];
                    }
                }
            }
        }
        dd($report_data);
        return $report_data;
        $report_data = [
            'data' => $employee_attendaces,
            'employee_id' => 1,
        ];
        return $report_data;
    }
}
