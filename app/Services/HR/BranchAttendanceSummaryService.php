<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
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
    protected WeeklyLeaveCalculator $weeklyLeaveCalculator;

    public function __construct()
    {
        $this->attendanceFetcher = new AttendanceFetcher(new EmployeePeriodHistoryService());
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
            ->with('employee:id,name,employee_no,salary,join_date,working_days,working_hours')
            ->get();

        $terminatedEmployeeIds = $terminatedRecords->pluck('employee_id')->toArray();

        $currentStaff    = [];
        $newStaff        = [];

        // Process active employees in DB-level chunks
        Employee::where('branch_id', $branchId)
            ->where('active', 1)
            ->select('id', 'name', 'employee_no', 'salary', 'join_date', 'working_days', 'working_hours')
            ->limit(20)
            ->chunk($chunkSize, function ($employees) use (&$currentStaff, &$newStaff, $terminatedEmployeeIds, $year, $month, $periodStart, $periodEnd, $monthDays) {

                $filtered = $employees->filter(fn($emp) => !in_array($emp->id, $terminatedEmployeeIds));

                if ($filtered->isEmpty()) return;

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

        return [
            'branch_id'        => $branchId,
            'year'             => $year,
            'month'            => $month,
            'period'           => $periodStart->format('M Y'),
            'current_staff'    => $currentStaff,
            'new_staff'        => $newStaff,
            'terminated_staff' => $terminatedStaff,
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
            // 1. Fetch attendance data (the main data source)
            $attendanceData  = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $periodStart, $periodEnd);
            $attendanceArray = $attendanceData->toArray();

            $stats = $attendanceArray['statistics'] ?? [];

            // 2. Weekly leave calculation (overtime_days / deduction_days)
            $totalDays  = $stats['required_days'] ?? $monthDays;
            $absentDays = $stats['absent'] ?? 0;

            $weeklyCalc = $this->weeklyLeaveCalculator->calculate($totalDays, $absentDays);
            $weeklyResult = $weeklyCalc['result'] ?? [];

            // 3. Approved overtime hours (simple DB query)
            $approvedOvertimeHours = $employee->overtimes()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('hours');

            // 4. Deduction hours: total expected - total actual worked
            $totalActualDuration = $attendanceArray['total_actual_duration_hours'] ?? '0:00:00';
            $totalExpectedDuration = $attendanceArray['total_duration_hours'] ?? '0:00:00';

            $actualMinutes   = $this->durationToMinutes($totalActualDuration);
            $expectedMinutes = $this->durationToMinutes($totalExpectedDuration);

            // Deduction hours = max(0, expected - actual) converted to hours
            $deductionMinutes = max(0, $expectedMinutes - $actualMinutes);
            $deductionHours = round($deductionMinutes / 60, 2);

            return [
                'employee_id'  => $employee->id,
                'employee_no'  => $employee->employee_no,
                'name'         => $employee->name,
                'salary'       => $employee->salary,
                'overtime'     => [
                    'days'  => $weeklyResult['overtime_days'] ?? 0,
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
