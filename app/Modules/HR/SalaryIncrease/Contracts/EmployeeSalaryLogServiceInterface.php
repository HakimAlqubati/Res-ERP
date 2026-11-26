<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Contracts;

use App\Models\Employee;
use App\Models\EmployeeSalaryLog;
use Carbon\Carbon;

interface EmployeeSalaryLogServiceInterface
{
    /**
     * Record the initial salary log for a newly created / imported employee (idempotent).
     */
    public function recordInitialSalary(
        Employee $employee,
        ?string $effectiveDate = null,
        string $source = EmployeeSalaryLog::SOURCE_INITIAL,
        ?int $actorId = null
    ): ?EmployeeSalaryLog;

    /**
     * Record a salary change into `hr_employee_salary_logs` with full locking,
     * forward payroll-lock guard, chronological validation, auto-baseline guard,
     * and conditional synchronization of `hr_employees.salary`.
     */
    public function recordSalaryChange(
        Employee $employee,
        float $newSalary,
        string $effectiveDate,
        string $source = EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
        ?int $requestId = null,
        ?string $notes = null,
        ?int $actorId = null,
        bool $syncEmployeeColumn = true,
        ?float $baselineSalaryOverride = null,
        bool $allowSameSalary = false
    ): EmployeeSalaryLog;

    /**
     * Synchronize `hr_employees.salary` for all employees whose effective salary
     * as of `$asOf` (defaults to `now()`) differs from their cached column value.
     *
     * @return array{checked: int, synced: int, failed: int, errors: array<int, array{employee_id: int, message: string}>}
     */
    public function syncCurrentEffectiveSalaries(?Carbon $asOf = null): array;
}
