<?php

namespace App\Services\HR\Payroll;

use App\Models\Employee;
use App\Models\Setting;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\SalaryHelpers\SalaryCalculatorService;
use Carbon\Carbon;

class PayrollSimulationService
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
            $totalApprovedOvertime =  '0:00:00';

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
                totalApprovedOvertime: $totalApprovedOvertime
            );

            $netSalary = $result['net_salary'] < 0 ? 0 : $result['net_salary'];
            $debt      = $result['net_salary'] < 0 ? abs($result['net_salary']) : 0;

            $results[] = [
                'success'     => true,
                'message'     => 'Simulation completed successfully.',
                'employee_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'name'        => $employee->name,
                'working_days' => $workDays,
                'working_hours' => $dailyHours,
                'monthly_salary' => $monthlySalary,
                'daily_salary' => $result['daily_rate'],
                'hourly_salary' => $result['hourly_rate'],
                'month_days' => $monthDays,

                'attendance_statistics' => $attendanceArray['statistics'],
                'total_approved_overtime' => $result['total_approved_overtime'],
                'total_actual_duration_hours' => $result['total_actual_duration'],
                'total_duration_hours' => $result['total_duration'],
                'tax' => $result['tax'] ,
                'late_hours' => $result['late_hours'],
                'transactions' => $result['transactions'] ?? [],
                'data' => [
                    'base_salary'       => $result['base_salary'],
                    'gross_salary'      => $result['gross_salary'],
                    'net_salary'        => $netSalary,
                    'absence_deduction' => $result['absence_deduction'],
                    'overtime_amount'   => $result['overtime_amount'],
                    'debt_amount'       => $debt,
                    'period_start'      => $periodStart->toDateString(),
                    'period_end'        => $periodEnd->toDateString(),
                    'transactions' => $result['transactions'] ?? [],
                ],
            ];
        }

        return $results;
    }
}
