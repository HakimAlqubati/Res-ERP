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

        $employees = Employee::whereIn('id', $employeeIds)->get();

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

            $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
            $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth();




            $attendanceData = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $periodStart, $periodEnd);
            // dd($attendanceData->toArray()['statistics']);
            $workDays   = $attendanceData['statistics']['work_days'] ?? 26; // عدد أيام العمل في الشهر
            $result = $this->salaryCalculatorService->calculate(
                employeeData: $attendanceData,
                salary: $monthlySalary,
                workDays: $workDays,
                dailyHours: 8
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

                // 'attendance' => $attendanceData->toArray(),
                'attendance_statistics' => $attendanceData['statistics'],
                'total_approved_overtime' => $attendanceData['total_approved_overtime'],
                'total_actual_duration_hours' => $attendanceData['total_actual_duration_hours'],
                'total_duration_hours' => $attendanceData['total_duration_hours'],
                'data'        => [
                    'base_salary'       => $result['base_salary'],
                    'gross_salary'      => $result['gross_salary'],
                    'net_salary'        => $netSalary,
                    'absence_deduction' => $result['absence_deduction'],
                    'partial_deduction' => $result['partial_deduction'],
                    'overtime_amount'   => $result['overtime_amount'],
                    'debt_amount'       => $debt,
                    'period_start'      => $periodStart->toDateString(),
                    'period_end'        => $periodEnd->toDateString(),
                ]
            ];
        }

        return $results;
    }

    protected function getWorkingConfig(Employee $employee): array
    {
        $mode = Setting::getSetting('working_policy_mode', 'global');

        if ($mode === 'custom_per_employee') {
            if (is_null($employee->working_days) || is_null($employee->working_hours)) {
                throw new \Exception("Missing working_days or working_hours for employee [{$employee->name}] (Employee No: {$employee->employee_no})");
            }

            return [
                'days'  => $employee->working_days,
                'hours' => $employee->working_hours,
            ];
        }

        // استخدام القيم من الإعدادات العامة
        return [
            'days'  => (int) Setting::getSetting('default_employee_working_days', 26),
            'hours' => (float) Setting::getSetting('default_employee_working_hours', 8),
        ];
    }
}
