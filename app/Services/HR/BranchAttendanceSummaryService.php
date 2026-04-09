<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\AttendanceHelpers\Reports\CachedAttendanceFetcher;
use Carbon\Carbon;

/**
 * Branch Attendance Summary Service (Lightweight)
 *
 * Generates a monthly attendance summary report directly from AttendanceFetcher,
 * bypassing the heavy PayrollSimulationService (no salary/tax/deduction calculation).
 *
 * Each employee entry includes:
 *  - Overtime (days & hours)
 *  - Deductions (days & hours)
 */
class BranchAttendanceSummaryService
{

    protected AttendanceFetcher $attendanceFetcher;
    protected CachedAttendanceFetcher $cachedAttendanceFetcher;
    protected WeeklyLeaveCalculator $weeklyLeaveCalculator;

    public function __construct()
    {
        $this->attendanceFetcher = new AttendanceFetcher(new EmployeePeriodHistoryService());
        // $this->cachedAttendanceFetcher = new CachedAttendanceFetcher($this->attendanceFetcher);
        $this->weeklyLeaveCalculator = new WeeklyLeaveCalculator();
    }

    /**
     * Generate the branch attendance summary report.
     */
    public function generate(int $branchId, int $year, int $month, int $chunkSize = 5): array
    {
        set_time_limit(300);

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth();

        // If current month, cap to today
        if ($year == now()->year && $month == now()->month) {
            $periodEnd = now()->endOfDay();
        }

        $monthDays = $periodStart->daysInMonth;

        // Terminated records this month
        $terminatedRecords = EmployeeServiceTermination::where('status', EmployeeServiceTermination::STATUS_APPROVED)
            ->whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('termination_date', [$periodStart, $periodEnd])
            ->with('employee:id,name,employee_no,salary,join_date,working_days,working_hours,discount_exception_if_attendance_late')
            ->get();

        $terminatedEmployeeIds = $terminatedRecords->pluck('employee_id')->toArray();

        $currentStaff    = [];
        $newStaff        = [];

        // Process active employees in DB-level chunks
        Employee::where('branch_id', $branchId)
            ->where('active', 1)
            ->select('id', 'name', 'employee_no', 'salary', 'join_date', 'working_days', 'working_hours', 'discount_exception_if_attendance_late')
            ->withSum(['overtimes as total_overtime' => function ($query) use ($year, $month) {
                $query->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->where('type', \App\Models\EmployeeOvertime::TYPE_BASED_ON_DAY);
            }], 'hours')
            ->withCount(['dailyOvertimes as manual_overtime_days' => function ($query) use ($year, $month) {
                $query->whereYear('date', $year)
                    ->whereMonth('date', $month);
            }])
            ->chunk(10, function ($employees) use (&$currentStaff, &$newStaff, $terminatedEmployeeIds, $year, $month, $periodStart, $periodEnd, $monthDays) {

                $filtered = $employees->filter(fn($emp) => !in_array($emp->id, $terminatedEmployeeIds));

                foreach ($filtered as $employee) {
                    $row = $this->processEmployee($employee, $periodStart, $periodEnd, $year, $month, $monthDays);

                    // Classify: new staff if joined this month
                    $isNew = $employee->join_date
                        && Carbon::parse($employee->join_date)->between($periodStart, $periodEnd);

                    if ($isNew) {
                        $row['join_date'] = Carbon::parse($employee->join_date)->format('Y-m-d');
                        $newStaff[] = $row;
                    } else {
                        $currentStaff[] = $row;
                    }
                }
            });

        // Process terminated employees
        $terminatedStaff = [];
        foreach ($terminatedRecords as $record) {
            $emp = $record->employee;
            if (!$emp) continue;

            $row = $this->processEmployee($emp, $periodStart, $periodEnd, $year, $month, $monthDays);
            $row['termination_date'] = Carbon::parse($record->termination_date)->format('Y-m-d');
            $terminatedStaff[] = $row;
        }

        $calculateTotals = function ($staffList) {
            $totals = [
                'present_days' => 0,
                'overtime_days' => 0,
                'overtime_hours' => 0,
                'deduction_days' => 0,
                'deduction_hours' => 0,
                'salary' => 0,
            ];
            foreach ($staffList as $row) {
                $totals['present_days'] += (float) ($row['attendance']['present_days'] ?? 0);
                $totals['overtime_days'] += (float) ($row['overtime']['days'] ?? 0);
                $totals['overtime_hours'] += (float) ($row['overtime']['hours'] ?? 0);
                $totals['deduction_days'] += (float) ($row['deductions']['days'] ?? 0);
                $totals['deduction_hours'] += (float) ($row['deductions']['hours'] ?? 0);
                $totals['salary'] += (float) ($row['salary'] ?? 0);
            }
            return $totals;
        };

        return [
            'branch_id'        => $branchId,
            'year'             => $year,
            'month'            => $month,
            'period'           => $periodStart->format('M Y'),
            'current_staff'    => $currentStaff,
            'new_staff'        => $newStaff,
            'terminated_staff' => $terminatedStaff,
            'totals'           => [
                'current_staff'    => $calculateTotals($currentStaff),
                'new_staff'        => $calculateTotals($newStaff),
                'terminated_staff' => $calculateTotals($terminatedStaff),
            ],
        ];
    }

    /**
     * Process a single employee — fetch attendance + compute summary.
     * Much lighter than full salary simulation.
     */
    protected function processEmployee(
        Employee $employee,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $year,
        int $month,
        int $monthDays
    ): array {
        try {
            $approvedOvertimeHours = (float) ($employee->total_overtime ?? 0);
            // 1. Fetch attendance data (the main data source)
            $attendanceData  = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $periodStart, $periodEnd);
            // $attendanceData  = $this->cachedAttendanceFetcher->fetchEmployeeAttendances($employee, $periodStart, $periodEnd);
            $attendanceArray = $attendanceData->toArray();

            $reportData = $attendanceArray['report_data'] ?? $attendanceArray;
            $stats = $reportData['statistics'] ?? ($attendanceArray['statistics'] ?? []);

            // 2. Weekly leave calculation (overtime_days / deduction_days)
            $totalDays  = $stats['required_days'] ?? $monthDays;
            $absentDays = $stats['absent'] ?? 0;

            $weeklyResult = $stats['weekly_leave_calculation']['result'] ?? [];




            // 4. Deduction hours: sum of missing hours, late, and early departure
            $missingMinutes        = $reportData['total_missing_hours']['total_minutes'] ?? 0;
            $earlyDepartureMinutes = $reportData['total_early_departure_minutes']['total_minutes'] ?? 0;
            $lateMinutes           = $reportData['late_hours']['totalMinutes'] ?? 0;

            $deductionMinutes = $missingMinutes + $earlyDepartureMinutes + $lateMinutes;
            $deductionHours = round($deductionMinutes / 60, 2);

            return [
                'employee_id'  => $employee->id,
                'employee_no'  => $employee->employee_no,
                'name'         => $employee->name,
                'salary'       => $employee->salary,
                'overtime'     => [
                    'days'  => ($weeklyResult['overtime_days'] ?? 0) + ($employee->manual_overtime_days ?? 0),
                    'hours' => (float) $approvedOvertimeHours,
                ],
                'deductions'   => [
                    'days'  => $weeklyResult['total_deduction_days'] ?? 0,
                    'hours' => $deductionHours,
                ],
                'attendance'   => [
                    'present_days' => $stats['present_days'] ?? 0,
                    'absent_days'  => $absentDays,
                    'total_days'   => $totalDays,
                ],
                'note'         => '',
            ];
        } catch (\Throwable $e) {
            return [
                'employee_id'  => $employee->id,
                'employee_no'  => $employee->employee_no,
                'name'         => $employee->name,
                'salary'       => $employee->salary,
                'overtime'     => ['days' => 0, 'hours' => 0],
                'deductions'   => ['days' => 0, 'hours' => 0],
                'attendance'   => [],
                'note'         => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Convert duration string "H:i:s" or "Xh Ym" to total minutes.
     */
    protected function durationToMinutes($duration): int
    {
        if (is_array($duration)) {
            return (($duration['hours'] ?? 0) * 60) + ($duration['minutes'] ?? 0);
        }

        if (is_string($duration) && str_contains($duration, ':')) {
            $parts = explode(':', $duration);
            return ((int)($parts[0] ?? 0) * 60) + (int)($parts[1] ?? 0);
        }

        return 0;
    }
}
