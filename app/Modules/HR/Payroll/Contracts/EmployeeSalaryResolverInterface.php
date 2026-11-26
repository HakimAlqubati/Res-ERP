<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Contracts;

use App\Models\Employee;

interface EmployeeSalaryResolverInterface
{
    /**
     * Resolve the effective monthly contracted salary for an employee in a given (year, month).
     */
    public function resolveForPeriod(Employee|int $employee, int $year, int $month): float;

    /**
     * Batch-resolve effective monthly contracted salaries for multiple employees in one (year, month).
     *
     * @param  array<int, int|string>  $employeeIds
     * @return array<int, float> Map of employee_id => effective salary
     */
    public function resolveMapForPeriod(array $employeeIds, int $year, int $month): array;

    /**
     * Batch-resolve effective monthly contracted salaries across multiple periods.
     *
     * @param  array<string, array<int, int|string>>  $periodEmployeeMap  Map of 'YYYY-MM' => employee IDs
     * @return array<string, array<int, float>> Map of 'YYYY-MM' => [employee_id => effective salary]
     */
    public function resolveMapForPeriods(array $periodEmployeeMap): array;
}
