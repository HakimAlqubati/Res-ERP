<?php

namespace App\Modules\HR\Payroll\Services;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Setting;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use Carbon\Carbon;
use App\Modules\HR\Payroll\Contracts\PayrollSimulatorInterface;

class PayrollSimulationService implements PayrollSimulatorInterface
{
    public function __construct(
        protected AttendanceFetcher $attendanceFetcher,
        protected SalaryCalculatorService $salaryCalculatorService
    ) {}

    /**
     * محاكاة احتساب الرواتب لمجموعة موظفين بدون حفظ في قاعدة البيانات
     */
    public function simulateForEmployees(array $employeeIds, int $year, int $month): array
    {
        $results = [];

        $employees    = Employee::whereIn('id', $employeeIds)->get();
        $periodStart  = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd    = Carbon::create($year, $month, 1)->endOfMonth();
        $monthDays    = $periodStart->daysInMonth;

        foreach ($employees as $employee) {
            $monthlySalary = $employee->salary;

            if (is_null($monthlySalary) || $monthlySalary == 0) {
                $results[] = [
                    'success'     => false,
                    'message'     => "Simulation failed: salary not set or zero for employee [{$employee->name}] (Employee No: {$employee->employee_no}) in branch [{$employee->branch?->name}]",
                    'employee_id' => $employee->id,
                    'employee_no' => $employee->employee_no,
                    'name'        => $employee->name,
                    'data'        => null,
                ];
                continue;
            }

            $dailyHours = $employee->working_hours ?? 0;
            $workDays   = $employee->working_days ?? 0;

            if ($dailyHours <= 0 || $workDays <= 0) {
                throw new \Exception("Missing or invalid working_hours or working_days for employee [{$employee->name}] (Employee No: {$employee->employee_no}) in branch [{$employee->branch?->name}]");
            }

            $attendanceData  = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $periodStart, $periodEnd);
            $attendanceArray = $attendanceData->toArray();

            $totalDuration         = $attendanceArray['total_duration_hours'] ?? '0:00:00';
            $totalActualDuration   = $attendanceArray['total_actual_duration_hours'] ?? '0:00:00';

            $totalApprovedOvertime =  $employee->overtimes()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('hours');

            // احتساب الراتب باستخدام القيم الجديدة
            $result = $this->salaryCalculatorService->calculate(
                employee: $employee,
                employeeData: $attendanceArray,
                salary: $monthlySalary,
                workingDays: $workDays,
                dailyHours: $dailyHours,
                monthDays: $monthDays,
                totalDuration: $totalDuration,
                totalActualDuration: $totalActualDuration,
                totalApprovedOvertime: $totalApprovedOvertime,
                periodYear: $year,
                periodMonth: $month

            );

            $netSalary = $result['net_salary'] < 0 ? 0 : $result['net_salary'];
            $debt      = $result['net_salary'] < 0 ? abs($result['net_salary']) : 0;

            $results[] = [
                'success'     => true,
                'message'     => 'Simulation completed successfully.',
                'employee_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'name'        => $employee->name,
                'working_days' => $result['working_days'], // Use the calculated working days (Month - 4)
                'working_hours' => $dailyHours,
                'monthly_salary' => $monthlySalary,
                'daily_salary' => round($result['daily_rate'], 2),
                'hourly_salary' => $result['hourly_rate'],
                'month_days' => $monthDays,

                'attendance_statistics' => $attendanceArray['statistics'],
                'total_approved_overtime' => $result['total_approved_overtime'],
                'total_actual_duration_hours' => $result['total_actual_duration'],
                'total_duration_hours' => $result['total_duration'],
                'missing_hours' => $result['missing_hours'],
                'missing_hours_deduction' => $result['missing_hours_deduction'],
                'early_departure_hours' => $result['early_departure_hours'],
                'early_departure_deduction' => $result['early_departure_deduction'],
                'total_deduction' => $result['total_deductions'] ?? 0,
                'tax' => $result['tax'] ?? 0,
                'late_hours' => $result['late_hours'],
                'transactions' => $result['transactions'] ?? [],
                'dynamic_deductions' => $result['dynamic_deductions'] ?? [],
                'penalty_total' => $result['penalty_total'] ?? 0,
                'penalties' => $result['penalties'] ?? [],
                'daily_rate_method' => $result['daily_rate_method'] ?? '',
                'data' => [
                    'base_salary'       => $result['base_salary'],
                    'gross_salary'      => $result['gross_salary'],
                    'net_salary'        => $netSalary,
                    'is_negative' => $result['is_negative'],
                    'absence_deduction' => $result['absence_deduction'],
                    'overtime_amount'   => $result['overtime_amount'],
                    'allowances' => $result['allowances'],
                    'allowance_total' => $result['allowance_total'],
                    'debt_amount'       => $debt,
                    'period_start'      => $periodStart->toDateString(),
                    'period_end'        => $periodEnd->toDateString(),
                    'transactions' => $result['transactions'] ?? [],
                ],
            ];
        }

        return $results;
    }

    /**
     * Run-aware simulation: use period/year/month from the given PayrollRun (no DB writes)
     */
    public function simulateForRunEmployees(PayrollRun $run, array $employeeIds): array
    {
        $results = [];

        $employees   = Employee::whereIn('id', $employeeIds)->get();
        $periodStart = Carbon::parse($run->period_start_date)->startOfDay();
        $periodEnd   = Carbon::parse($run->period_end_date)->endOfDay();
        $monthDays   = Carbon::create($run->year, $run->month, 1)->daysInMonth;

        foreach ($employees as $employee) {
            $monthlySalary = (float) ($employee->salary ?? 0);

            if ($monthlySalary <= 0) {
                $results[] = [
                    'success'     => false,
                    'message'     => "Simulation failed: salary not set or zero for employee [{$employee->name}] (Employee No: {$employee->employee_no}) in branch [{$employee->branch?->name}]",
                    'employee_id' => $employee->id,
                    'employee_no' => $employee->employee_no,
                    'name'        => $employee->name,
                    'data'        => null,
                ];
                continue;
            }

            $dailyHours = (int) ($employee->working_hours ?? 0);
            $workDays   = (int) ($employee->working_days ?? 0);

            if ($dailyHours <= 0 || $workDays <= 0) {
                throw new \Exception("Missing or invalid working_hours or working_days for employee [{$employee->name}] (Employee No: {$employee->employee_no}) in branch [{$employee->branch?->name}]");
            }

            // Attendance for the run's exact period
            $attendanceData  = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $periodStart, $periodEnd);
            $attendanceArray = (array) $attendanceData->toArray();

            $totalDuration         = $attendanceArray['total_duration_hours']        ?? '0:00:00';
            $totalActualDuration   = $attendanceArray['total_actual_duration_hours'] ?? '0:00:00';
            $totalApprovedOvertime = $employee->overtimes()
                ->whereYear('date', $run->year)
                ->whereMonth('date', $run->month)
                ->sum('hours');

            // Salary calculation
            $result = $this->salaryCalculatorService->calculate(
                employee: $employee,
                employeeData: $attendanceArray,
                salary: $monthlySalary,
                workingDays: $workDays,
                dailyHours: $dailyHours,
                monthDays: $monthDays,
                totalDuration: $totalDuration,
                totalActualDuration: $totalActualDuration,
                totalApprovedOvertime: $totalApprovedOvertime
            );

            $netSalary = $result['net_salary'] < 0 ? 0 : $result['net_salary'];
            $debt      = $result['net_salary'] < 0 ? abs($result['net_salary']) : 0;

            $results[] = [
                'success'                 => true,
                'message'                 => 'Simulation completed successfully.',
                'employee_id'             => $employee->id,
                'employee_no'             => $employee->employee_no,
                'name'                    => $employee->name,
                'working_days'            => $result['working_days'], // Use the calculated working days
                'daily_rate_method' => $result['daily_rate_method'] ?? '',
                'working_hours'           => $dailyHours,
                'monthly_salary'          => $monthlySalary,
                'daily_salary'            => $result['daily_rate'],
                'hourly_salary'           => $result['hourly_rate'],
                'month_days'              => $monthDays,
                'attendance_statistics'   => $attendanceArray['statistics'] ?? [],
                'total_approved_overtime' => $result['total_approved_overtime'] ?? ['hours' => 0, 'minutes' => 0],
                'total_actual_duration_hours' => $result['total_actual_duration'] ?? ['hours' => 0, 'minutes' => 0],
                'total_duration_hours'        => $result['total_duration'] ?? ['hours' => 0, 'minutes' => 0],
                'tax'                     => $result['tax'] ?? 0,
                'late_hours'              => $result['late_hours'] ?? 0,
                'transactions'            => $result['transactions'] ?? [],
                'data' => [
                    'base_salary'       => $result['base_salary'],
                    'gross_salary'      => $result['gross_salary'],
                    'net_salary'        => $netSalary,
                    'absence_deduction' => $result['absence_deduction'],
                    'overtime_amount'   => $result['overtime_amount'],
                    'debt_amount'       => $debt,
                    'period_start'      => $periodStart->toDateString(),
                    'period_end'        => $periodEnd->toDateString(),
                    'transactions'      => $result['transactions'] ?? [],
                ],
            ];
        }

        return $results;
    }
}
