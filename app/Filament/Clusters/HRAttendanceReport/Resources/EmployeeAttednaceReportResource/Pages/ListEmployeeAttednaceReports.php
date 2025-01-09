<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
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

class ListEmployeeAttednaceReports extends ListRecords implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = EmployeeAttednaceReportResource::class;

    public $showDetailsModal = false;
    public $modalData = [];
    public function showDetails($date, $employeeId, $periodId)
    {
        // Replace with your actual data-fetching logic if needed
        $AttendanceDetails = getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);
        $this->modalData = $AttendanceDetails->toArray();
        //  dd($this->modalData);
        $this->showDetailsModal = true; // This opens the modal
    }

    protected function getForms(): array
    {

        return array_merge(
            parent::getForms(),

            [
                //merge your own form with default ones
                'customForm' => $this->makeForm()->disabled()
                    ->fill([
                        'hi' => 'drgin',
                    ])
                    ->schema([
                        TextInput::make('hi'),
                    ])
                    ->model(EmployeeAttednaceReportResource::class),
            ]
        );
    }

    // public function mount(): void

    // {

    // $this->form->fill();

    // }

    protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employee';
    protected function getViewData(): array
    {
        if (!isStuff()) {
            $employee_id = $this->getTable()->getFilters()['employee_id']->getState()['value'];
        } else {
            $employee_id = auth()->user()?->employee?->id;
        }

        $start_date = $this->getTable()->getFilters()['date_range']->getState()['start_date'];
        $end_date = $this->getTable()->getFilters()['date_range']->getState()['end_date'];
        $show_day = $this->getTable()->getFilters()['show_extra_fields']->getState()['show_day'];
        
        // Initialize total counters
        $totalSupposed = '0 h 0 m';
        $totalWorked = 0;
        $totalApproved = 0;
        $totalMinutes = 0;
        // $report_data = $this->getReportData2($employee_id, $start_date, $end_date);
        $data = employeeAttendances($employee_id, $start_date, $end_date);
        // dd($data);

        // Calculate totals from the attendance data
        foreach ($data as $date => $dayData) {
            $periodIds = collect($dayData['periods'])->pluck('period_id')->toArray();
            foreach ($periodIds as $periodId) {

                $totalMinutes += WorkPeriod::find($periodId)
                    ->calculateTotalSupposedDurationForDays((count($data) - LeaveType::getMonthlyCountDaysSum()));
            }
            break;
        }
        // Now convert the total minutes to hours and minutes
        $totalHours = intdiv($totalMinutes, 60); // Get the total hours
        $remainingMinutes = $totalMinutes % 60; // Get the remaining minutes
        // Format the result
        $totalSupposed = sprintf('%02d h %02d m', $totalHours, $remainingMinutes);

        foreach ($data as $date => $dayData) {
            foreach ($dayData['periods'] ?? [] as $period) {
                // $arr[] = $period['period_id'];
                $totalWorked += $this->parseDuration($period['total_hours'] ?? '0 h 0 m');
                $totalApproved += $this->parseDuration($period['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? '0 h 0 m');
            }
        }

        // dd($data);
        return [
            'report_data' => $data,
            'show_day' => $show_day,
            'employee_id' => $employee_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'totalSupposed' => $totalSupposed,
            'totalWorked' => $this->formatDuration($totalWorked),
            'totalApproved' => $this->formatDuration($totalApproved),
        ];
    }

    public function getReportData2($employee_id, $start_date, $end_date)
    {
        $employee = Employee::find($employee_id);
        $leaveApplications = $employee?->approvedLeaveApplications()
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('from_date', [$start_date, $end_date])
                    ->orWhereBetween('to_date', [$start_date, $end_date]);
            })
            ->select('from_date', 'to_date', 'leave_type_id')
            ->get();

        // Initialize an array to store all leave dates
        $leaveDates = [];
        if ($employee) {
            foreach ($leaveApplications as $leave) {

                $fromDate = Carbon::parse($leave->from_date);
                $toDate = Carbon::parse($leave->to_date);

                // Create a loop to generate the list of dates
                for ($date = $fromDate; $date->lte($toDate); $date->addDay()) {
                    // $leaveDates[$date->format('Y-m-d')] = $date->format('Y-m-d'); // Add date to the array
                    $leaveDates[$date->format('Y-m-d')] = 'Leave application approved for (' . $leave?->leaveType?->name . ')'; // Add date to the array
                }
            }
        }
        // Loop through each leave application and generate dates between 'from_date' and 'to_date'
        // if (is_array($leaveApplications) && count($leaveApplications) > 0) {
        // }
        // dd($leaveApplications, $leaveDates);
        $report_data['data'] = [];
        $holidays = Holiday::where('active', 1)
            ->whereBetween('from_date', [$start_date, $end_date])
            ->orWhereBetween('to_date', [$start_date, $end_date])
            ->select('from_date', 'to_date', 'count_days', 'name')
            ->get()
            ->keyBy('from_date');

        $weekend_days = json_decode(WeeklyHoliday::select('days')->first()->days);

        $period = Carbon::parse($start_date)->toPeriod($end_date);
        // dd($leaveApplications, $leaveDates, $start_date, $end_date, $employee_id);
        // Loop through all dates and check if there is attendance data for each date
        foreach ($period as $date) {

            $formatted_date = $date->format('Y-m-d');
            $day_of_week = $date->format('l'); // Get the day name (e.g., "Saturday")

            $work_periods = WorkPeriod::where('active', 1)->get()->map(function ($period) {
                $period->days = json_decode($period->days);
                return $period;
            });

            // Find matching work periods for the given day
            $matching_periods = $work_periods->filter(function ($period) use ($day_of_week) {
                return in_array($day_of_week, $period->days);
            });

            // Check if the date is a holiday
            foreach ($matching_periods as $matching_period) {

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
                        'hr_attendances.day',
                        'hr_attendances.supposed_duration_hourly',
                        'hr_attendances.actual_duration_hourly',
                        'hr_attendances.late_departure_minutes',
                        'hr_attendances.early_arrival_minutes',
                        'hr_attendances.status',
                        'hr_attendances.period_id',
                        'hr_attendances.id',

                    )
                    ->whereBetween('hr_attendances.check_date', [$start_date, $end_date])
                    ->where('hr_attendances.employee_id', $employee_id)
                    ->where('hr_attendances.period_id', $matching_period->id)
                    ->orderBy('hr_attendances.check_date')
                    ->get()
                    ->groupBy('check_date');
                // return $employee_attendances;

                // Convert employee attendances to array if it's a collection
                $employee_attendances_array = $employee_attendances->toArray();

                if (isset($holidays[$formatted_date])) {
                    // If the date is a holiday, add it as a holiday

                    $holiday = $holidays[$formatted_date];
                    $report_data['data'][$formatted_date][$matching_period->id][] = (object) [
                        'period_id' => $matching_period->id,
                        'employee_id' => $employee_id,
                        'employee_no' => 'N/A', // Adjust accordingly
                        'employee_name' => 'N/A', // Adjust accordingly
                        'check_type' => 'Holiday',
                        'check_date' => $formatted_date,
                        'check_time' => null,
                        'day' => $day_of_week, // Add the day for holidays
                        'holiday_name' => 'Holiday of (' . $holiday->name . ')', // Add the holiday name
                    ];
                } else if (isset($leaveDates[$formatted_date])) {
                    // If the date is a approved leave application, add it as a approved leave application
                    // dd($leaveDates,array_values($leaveDates));
                    $leave_date = $leaveDates[$formatted_date];
                    $report_data['data'][$formatted_date][$matching_period->id][] = (object) [
                        'period_id' => $matching_period->id,
                        'employee_id' => $employee_id,
                        'employee_no' => 'N/A', // Adjust accordingly
                        'employee_name' => 'N/A', // Adjust accordingly
                        'check_type' => 'ApprovedLeaveApplication',
                        'check_date' => $formatted_date,
                        'check_time' => null,
                        'day' => $day_of_week,
                        'leave_type_name' => $leave_date,
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
                                if ($attendance->period_id == $matching_period->id) {
                                    $report_data['data'][$formatted_date][$matching_period->id][] = (object) [
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
                                        'period_id' => $matching_period->id,
                                        'period_start_at' => $matching_period->start_at,
                                        'period_end_at' => $matching_period->end_at,
                                        'id' => $attendance->id,
                                    ];
                                }
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
                            $report_data['data'][$formatted_date][$matching_period->id][] = (object) [
                                'period_id' => $matching_period->id,
                                'employee_id' => $employee_id,
                                'employee_no' => 'N/A', // Adjust accordingly
                                'employee_name' => 'N/A', // Adjust accordingly
                                'check_type' => 'Absent',
                                'period_start_at' => $matching_period->start_at,
                                'period_end_at' => $matching_period->end_at,
                                'check_date' => $formatted_date,
                                'check_time' => null,
                                'day' => $day_of_week, // Add the day for absent days
                            ];
                        }
                    }
                }
            }
        }
        // dd($report_data);
        return $report_data;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'active' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('active', true)),
            'inactive' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('active', false)),
        ];
    }

    private function parseDuration($duration)
    {
        if (preg_match('/(\d+)\s*h\s*(\d+)\s*m/', $duration, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            return $hours * 60 + $minutes; // Convert to total minutes
        }
        return 0;
    }

    private function formatDuration($totalMinutes)
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return "{$hours} h {$minutes} m";
    }
}
