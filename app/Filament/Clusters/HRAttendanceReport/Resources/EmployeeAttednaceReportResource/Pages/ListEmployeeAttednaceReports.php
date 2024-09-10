<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
use App\Models\WeeklyHoliday;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListEmployeeAttednaceReports extends ListRecords
{
    protected static string $resource = EmployeeAttednaceReportResource::class;

    protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employee';
    protected function getViewData(): array
    {
        $updates = request()->input('components.0.updates', []);
        $start_date = $updates['tableFilters.date_range.start_date'] ?? null;
        $employee_id = $updates['tableFilters.employee_id.value'] ?? null;
        $end_date = $updates['tableFilters.date_range.end_date'] ?? null;

        $report_data = $this->getReportData($employee_id, $start_date, $end_date);

        return [
            'report_data' => $report_data['data'],
            'employee_id' => $employee_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];
    }
    public function getReportData($employee_id, $start_date, $end_date)
    {
        $updates = request()->input('components.0.updates', []);
        $start_date = $updates['tableFilters.date_range.start_date'] ?? null;
        $end_date = $updates['tableFilters.date_range.end_date'] ?? null;
        $employee_id = $updates['tableFilters.employee_Id.value'] ?? null;
        $employee_id = 15;
        $start_date = '2024-09-09';
        $end_date = '2024-09-10';
        $report_data['data'] = [];

        $weekend_days = json_decode(WeeklyHoliday::select('days')->first()->days);


        $employee_attendances = DB::table('hr_attendances')
            ->join('hr_employees', 'hr_attendances.employee_id', '=', 'hr_employees.id')
            ->select(
                'hr_attendances.employee_id',
                'hr_employees.employee_no as employee_no',
                'hr_employees.name as employee_name',
                'hr_attendances.check_type',
                'hr_attendances.check_date',
                'hr_attendances.check_time',
                'hr_attendances.day',
            )
            ->whereBetween('hr_attendances.check_date', [$start_date, $end_date])
            ->where('hr_attendances.employee_id', $employee_id)
            ->get()
            // ->keyBy('check_date') // Use keyBy on the collection
            ->toArray();

        // dd($employee_attendances, $employee_id,'start '.$start_date,'enddate '.$end_date);
        // Create a list of all dates within the range
        $period = Carbon::parse($start_date)->toPeriod($end_date);


        // Loop through all dates and check if there is attendance data for each date
        foreach ($period as $date) {
            $formatted_date = $date->format('Y-m-d');
            $day_of_week = $date->format('l'); // Get the day name (e.g., "Saturday")

            if (isset($employee_attendances[$formatted_date])) {
                // Add attendance data if it exists for the date, and include the day
                $report_data['data'][] = (object) [
                    'employee_id'   => $employee_attendances[$formatted_date]->employee_id,
                    'employee_no'   => $employee_attendances[$formatted_date]->employee_no,
                    'employee_name' => $employee_attendances[$formatted_date]->employee_name,
                    'check_type'    => $employee_attendances[$formatted_date]->check_type,
                    'check_date'    => $formatted_date,
                    'check_time'    => $employee_attendances[$formatted_date]->check_time,
                    'day'           => $employee_attendances[$formatted_date]->day // Include the day from attendance
                ];
            } else {
                // Check if the day is a weekend
                if (in_array($day_of_week, $weekend_days)) {
                    // Add a row with 'Weekend' status for weekend days
                    $report_data['data'][] = (object) [
                        'employee_id'   => $employee_id,
                        'employee_no'   => 'N/A', // Adjust accordingly
                        'employee_name' => 'N/A', // Adjust accordingly
                        'check_type'    => 'Weekend',
                        'check_date'    => $formatted_date,
                        'check_time'    => null,
                        'day'           => $day_of_week // Add the day for weekend
                    ];
                } else {
                    // Add a row with 'Absent' status for missing dates that are not weekends
                   $report_data['data'][] = (object) [
                        'employee_id'   => $employee_id,
                        'employee_no'   => 'N/A', // Adjust accordingly
                        'employee_name' => 'N/A', // Adjust accordingly
                        'check_type'    => 'Absent',
                        'check_date'    => $formatted_date,
                        'check_time'    => null,
                        'day'           => $day_of_week // Add the day for absent days
                    ];
                }
            }
        }

        dd($report_data,$employee_attendances);
        $report_data = [
            'data' => $employee_attendaces,
            'employee_id' => 1
        ];
        return $report_data;
    }
}
