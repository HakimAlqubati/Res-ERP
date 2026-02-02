<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeesAttednaceReportResource;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\WeeklyHoliday;
use App\Models\WorkPeriod;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListEmployeesAttednaceReport extends ListRecords
{
    protected static string $resource = EmployeesAttednaceReportResource::class;
    protected   string $view     = 'filament.pages.hr-reports.attendance.pages.attendance-employees-with-header-fixed-new';

    public $showDetailsModal = false;
    public $modalData        = [];
    /**
     * @param  Model|array  $record
     */
    public function getTableRecordKey(Model|array $record): string
    {
        if (is_array($record)) {
            // لو البيانات جاية كمصفوفة
            return (string) ($record['employee_id'] ?? $record['id'] ?? '');
        }

        // لو Model
        $attributes = $record->getAttributes();

        return (string) ($attributes['employee_id'] ?? $record->getKey());
    }

    private function parseDuration($duration)
    {
        // Match hours and minutes using regex
        if (preg_match('/(\d+)\s*h\s*(\d+)\s*m/', $duration, $matches)) {
            $hours   = (int) $matches[1];
            $minutes = (int) $matches[2];
            return $hours * 60 + $minutes; // Convert to total minutes
        }
        return 0; // Default to 0 if parsing fails
    }

    private function formatDuration($totalMinutes)
    {
        $hours   = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return "{$hours} h {$minutes} m";
    }

    public function getViewData(): array
    {
        $branch_id = $this->getTable()->getFilters()['branch_id']->getState()['value'];
        $date      = $this->getTable()->getFilters()['filter_date']->getState()['date'];

        $report_data = [];

        $employeesPaginator = [];
        $employeeIds        = [];

        // If no branch is selected, return empty data
        if (empty($branch_id) || $branch_id == '') {
            return [
                'employees'     => [],
                'report_data'   => [],
                'branch_id'     => null,
                'date'          => $date,
                'totalSupposed' => $this->formatDuration(0),
                'totalWorked'   => $this->formatDuration(0),
                'totalApproved' => $this->formatDuration(0),
            ];
        }

        $employeesPaginator = Employee::where('branch_id', $branch_id)->active()
            ->select('id', 'name')
            ->paginate(100);
        $employeeIds = $employeesPaginator->pluck('id')->toArray();

        $service = new EmployeesAttendanceOnDateService(new AttendanceFetcher(new EmployeePeriodHistoryService()));
        $reports = $service->fetchAttendances($employeeIds, $date);

        // dd($reports);
        // بعد جلب التقارير:
        $employees = $reports->map(function ($item) {
            // تحويل attendance_report إلى مصفوفة (لأنها Collection)
            $attendance_report = $item['attendance_report']->map(function ($dayData) {
                if (!is_array($dayData)) {
                    return []; // أو يمكنك تسجيل خطأ أو تجاهله حسب الحاجة
                }

                $dayData['periods'] = isset($dayData['periods']) && $dayData['periods'] instanceof \Illuminate\Support\Collection
                    ? $dayData['periods']->toArray()
                    : (is_array($dayData['periods'] ?? null) ? $dayData['periods'] : []);

                return $dayData;
            })->toArray();

            return [
                'employee'          => $item['employee'],
                'attendance_report' => $attendance_report,
            ];
        })->values()->toArray();

        // Calculate totals
        $totalSupposed = 0;
        $totalWorked   = 0;
        $totalApproved = 0;

        // dd($employees,$report_data);
        return [
            'employees'   => $employees,
            'report_data'   => $report_data,
            'branch_id'     => $branch_id,
            'date'          => $date,
            // 'totalSupposed' => $totalSupposed,
            'totalSupposed' => $this->formatDuration($totalSupposed),
            'totalWorked'   => $this->formatDuration($totalWorked),
            'totalApproved' => $this->formatDuration($totalApproved),
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
        $day_of_week    = date('l', strtotime($date));

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
                        ->where('accepted', 1)
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
                        $holiday                                             = $holidays[$formatted_date];
                        $report_data['data'][$employee->name][$period->id][] = (object) [
                            'period_id'     => $period->id,
                            'employee_id'   => $employee->id,
                            'employee_no'   => 'N/A',
                            'employee_name' => $employee->name,
                            'check_type'    => 'Holiday',
                            'check_date'    => $formatted_date,
                            'check_time'    => null,
                            'day'           => $day_of_week,
                            'holiday_name'  => 'Holiday of (' . $holiday->name . ')',
                        ];
                    } elseif (isset($leaveDates[$formatted_date])) {
                        // If the date is a leave, add it as a leave
                        $leave_date                                          = $leaveDates[$formatted_date];
                        $report_data['data'][$employee->name][$period->id][] = (object) [
                            'period_id'       => $period->id,
                            'employee_id'     => $employee->id,
                            'employee_no'     => 'N/A',
                            'employee_name'   => $employee->name,
                            'check_type'      => 'ApprovedLeaveApplication',
                            'check_date'      => $formatted_date,
                            'check_time'      => null,
                            'day'             => $day_of_week,
                            'leave_type_name' => $leave_date,
                        ];
                    } elseif ($attendances->isNotEmpty()) {
                        // If there are attendance records, add them
                        foreach ($attendances as $attendance) {
                            $report_data['data'][$employee->name][$period->id][] = (object) [
                                'employee_id'              => $attendance->employee_id,
                                'employee_no'              => $attendance->employee_no,
                                'employee_name'            => $attendance->employee_name,
                                'check_type'               => $attendance->check_type,
                                'check_date'               => $attendance->check_date,
                                'check_time'               => $attendance->check_time,
                                'day'                      => $attendance->day,
                                'actual_duration_hourly'   => $attendance->actual_duration_hourly,
                                'supposed_duration_hourly' => $attendance->supposed_duration_hourly,
                                'early_arrival_minutes'    => $attendance->early_arrival_minutes,
                                'late_departure_minutes'   => $attendance->late_departure_minutes,
                                'status'                   => $attendance->status,
                                'period_id'                => $period->id,
                                'period_start_at'          => $period->start_at,
                                'period_end_at'            => $period->end_at,
                                'id'                       => $attendance->id,
                            ];
                        }
                    } else {
                        // If no attendance, check if it's a weekend
                        if (in_array($day_of_week, $weekend_days)) {
                            $report_data['data'][$employee->name][$period->id][] = (object) [
                                'employee_id'   => $employee->id,
                                'employee_no'   => 'N/A',
                                'employee_name' => $employee->name,
                                'check_type'    => 'Weekend',
                                'check_date'    => $formatted_date,
                                'check_time'    => null,
                                'day'           => $day_of_week,
                            ];
                        } else {
                            // Otherwise, mark as absent
                            $report_data['data'][$employee->name][$period->id][] = (object) [
                                'employee_id'     => $employee->id,
                                'employee_no'     => 'N/A',
                                'employee_name'   => $employee->name,
                                'check_type'      => 'Absent',
                                'period_id'       => $period->id,
                                'period_start_at' => $period->start_at,
                                'period_end_at'   => $period->end_at,
                                'check_date'      => $formatted_date,
                                'check_time'      => null,
                                'day'             => $day_of_week,
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
            $toDate   = Carbon::parse($leave->to_date);

            for ($date = $fromDate; $date->lte($toDate); $date->addDay()) {
                $leaveDates[$date->format('Y-m-d')] = 'Leave application approved for (' . $leave->leaveType->name . ')';
            }
        }

        return $leaveDates;
    }

    // public function showDetails($date, $employeeId, $periodId)
    // {
    //     $AttendanceDetails = getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);
    //     dd($AttendanceDetails,$periodId,$date,$employeeId);
    //     return dd($AttendanceDetails->toArray());
    // }

    // Add a method to handle showing the modal with data

    public function showDetails($date, $employeeId, $periodId)
    {
        // Replace with your actual data-fetching logic if needed
        $AttendanceDetails = getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);

        $this->modalData = [
            'data' => $AttendanceDetails->toArray(),
            'date' => $date
        ];

        //  dd($this->modalData);
        $this->showDetailsModal = true; // This opens the modal
        $this->dispatch('open-modal', id: 'attendance-details');
    }
}
