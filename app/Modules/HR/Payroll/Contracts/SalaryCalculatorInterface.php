<?php

namespace App\Modules\HR\Payroll\Contracts;

use App\Models\Employee;

/**
 * Interface for salary calculation services.
 * 
 * Provides a contract for calculating employee salaries including
 * base salary, overtime, deductions, allowances, and penalties.
 */
interface SalaryCalculatorInterface
{
    /**
     * Calculate salary for an employee.
     *
     * @param Employee $employee The employee to calculate salary for
     * @param array $employeeData Structured attendance & stats payload
     * @param float $salary Base salary (monthly)
     * @param int $workingDays Working days in schedule
     * @param int $dailyHours Daily hours
     * @param int $monthDays Actual month days (28..31)
     * @param string|array $totalDuration Total duration worked
     * @param string|array $totalActualDuration Total actual duration
     * @param float $totalApprovedOvertime Approved overtime hours
     * @param int|null $periodYear Year of the period
     * @param int|null $periodMonth Month of the period
     * @return array Calculation result with all salary components
     */
    public function calculate(
        Employee $employee,
        array $employeeData,
        float $salary,
        int $workingDays,
        int $dailyHours,
        int $monthDays,
        string|array $totalDuration,
        string|array $totalActualDuration,
        float $totalApprovedOvertime,
        ?int $periodYear = null,
        ?int $periodMonth = null,
    ): array;
}
