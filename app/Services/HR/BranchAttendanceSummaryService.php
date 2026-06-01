<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use App\Modules\HR\AttendanceReports\Contracts\AttendanceReportInterface;
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

    protected AttendanceReportInterface $reportManager;

    public function __construct(AttendanceReportInterface $reportManager)
    {
        $this->reportManager = $reportManager;
    }

    /**
     * Generate the branch attendance summary report.
     */
    public function generate(int $branchId, int $year, int $month, int $chunkSize = 5): array
    {
        // set_time_limit(300);

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth();

        // If current month, cap to today
        if ($year == now()->year && $month == now()->month) {
            $periodEnd = now()->endOfDay();
        }

        $monthDays = $periodStart->daysInMonth;

        $employeeIdsInBranch = \App\Models\EmployeeBranchLog::getEmployeesForBranchInRange($branchId, $periodStart, $periodEnd);

        // Terminated records this month
        $terminatedRecords = EmployeeServiceTermination::where('status', EmployeeServiceTermination::STATUS_APPROVED)
            ->whereIn('employee_id', $employeeIdsInBranch)
            ->whereBetween('termination_date', [$periodStart, $periodEnd])
            ->with('employee:id,name,employee_no,salary,join_date,working_days,working_hours,discount_exception_if_attendance_late')
            ->get();

        $terminatedEmployeeIds = $terminatedRecords->pluck('employee_id')->toArray();

        $currentStaff    = [];
        $newStaff        = [];

        // Process active employees in DB-level chunks
        Employee::whereIn('id', $employeeIdsInBranch)
            ->where('active', 1)
            ->select('id', 'name', 'employee_no', 'salary', 'join_date', 'working_days', 'working_hours', 'discount_exception_if_attendance_late', 'has_auto_weekly_leave')
            ->withSum(['overtimes as total_overtime' => function ($query) use ($year, $month) {
                $query->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->where('type', \App\Models\EmployeeOvertime::TYPE_BASED_ON_DAY);
            }], 'hours')
            ->withCount(['dailyOvertimes as manual_overtime_days' => function ($query) use ($year, $month) {
                $query->whereYear('date', $year)
                    ->whereMonth('date', $month);
            }])
            ->chunk(50, function ($employees) use (&$currentStaff, &$newStaff, $terminatedEmployeeIds, $year, $month, $periodStart, $periodEnd, $monthDays, $branchId) {

                $filtered = $employees->filter(fn($emp) => !in_array($emp->id, $terminatedEmployeeIds));

                // Optimized: Fetch all attendance data for the entire chunk in one bulk request
                $chunkReportMap = $this->reportManager->getEmployeesRangeReport($filtered, $periodStart, $periodEnd);

                foreach ($filtered as $employee) {
                    $employeeReport = $chunkReportMap->get($employee->id) ?? collect();
                    $row = $this->processEmployee($employee, $employeeReport, $periodStart, $periodEnd, $year, $month, $monthDays, $branchId);

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
        if ($terminatedRecords->isNotEmpty()) {
            $terminatedEmployees = $terminatedRecords->pluck('employee')->filter();
            $terminatedReportMap = $this->reportManager->getEmployeesRangeReport($terminatedEmployees, $periodStart, $periodEnd);

            foreach ($terminatedRecords as $record) {
                $emp = $record->employee;
                if (!$emp) continue;

                $empReport = $terminatedReportMap->get($emp->id) ?? collect();
                $row = $this->processEmployee($emp, $empReport, $periodStart, $periodEnd, $year, $month, $monthDays, $branchId);
                $row['termination_date'] = Carbon::parse($record->termination_date)->format('Y-m-d');
                $terminatedStaff[] = $row;
            }
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
        \Illuminate\Support\Collection $attendanceData,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $year,
        int $month,
        int $monthDays,
        int $branchId
    ): array {
        try {
            $approvedOvertimeHours = (float) ($employee->total_overtime ?? 0);
            $attendanceArray = $attendanceData->toArray();

            $presentDays = 0;
            $absentDays  = 0;
            $weeklyLeaveDays = 0;
            $totalDays   = 0;
            $missingMinutes = 0;
            $earlyDepartureMinutes = 0;
            $lateMinutes = 0;

            foreach ($attendanceArray as $date => $dayData) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    continue;
                }

                if (($dayData['branch_id'] ?? null) != $branchId) {
                    continue;
                }

                $totalDays++;
                $status = $dayData['day_status'] ?? '';

                if (in_array($status, [
                    \App\Enums\HR\Attendance\AttendanceReportStatus::Present->value,
                    \App\Enums\HR\Attendance\AttendanceReportStatus::IncompleteCheckoutOnly->value,
                ])) {
                    $presentDays++;
                } elseif (in_array($status, [
                    \App\Enums\HR\Attendance\AttendanceReportStatus::Absent->value,
                    \App\Enums\HR\Attendance\AttendanceReportStatus::Partial->value,
                    \App\Enums\HR\Attendance\AttendanceReportStatus::IncompleteCheckinOnly->value,
                ])) {
                    $absentDays++;
                } elseif ($status === \App\Enums\HR\Attendance\AttendanceReportStatus::WeeklyLeave->value) {
                    $weeklyLeaveDays++;
                }

                if (!empty($dayData['periods'])) {
                    foreach ($dayData['periods'] as $period) {
                        $checkoutData = $period['attendances']['checkout']['lastcheckout'] ?? [];
                        if (!empty($checkoutData)) {
                            $missingMinutes += (float) ($checkoutData['missing_hours']['total_minutes'] ?? 0);
                            $earlyDepartureMinutes += (float) ($checkoutData['early_departure_minutes'] ?? 0);
                        }

                        $checkinData = $period['attendances']['checkin'][0] ?? [];
                        if (!empty($checkinData) && !empty($checkoutData)) {
                            $lateMinutes += (float) ($checkinData['delay_minutes'] ?? 0);
                        }
                    }
                }
            }
 
            $weeklyLeaveDeductionDays = $absentDays;
            $autoOvertimeDays = 0;

            if ($employee->has_auto_weekly_leave) {
                $workDaysPerLeave = 6;
                if (class_exists(\App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator::class)) {
                    $workDaysPerLeave = \App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator::WORK_DAYS_PER_LEAVE;
                }
                $entitledLeaves = min(4, floor($presentDays / $workDaysPerLeave));

                $totalOffDays = $absentDays + $weeklyLeaveDays;

                if ($entitledLeaves >= $totalOffDays) {
                    $weeklyLeaveDeductionDays = 0;
                    $autoOvertimeDays = min(4, $entitledLeaves - $totalOffDays);
                } else {
                    $weeklyLeaveDeductionDays = $totalOffDays - $entitledLeaves;
                }
            }

            $deductionMinutes = $missingMinutes + $earlyDepartureMinutes + $lateMinutes;
            $deductionHours = round($deductionMinutes / 60, 2);

            return [
                'employee_id'  => $employee->id,
                'employee_no'  => $employee->employee_no,
                'name'         => $employee->name,
                'salary'       => $employee->salary,
                'overtime'     => [
                    'days'  => $autoOvertimeDays + ($employee->manual_overtime_days ?? 0),
                    'hours' => $approvedOvertimeHours,
                ],
                'deductions'   => [
                    'days'  => $weeklyLeaveDeductionDays,
                    'hours' => $deductionHours,
                ],
                'attendance'   => [
                    'present_days' => $presentDays,
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
