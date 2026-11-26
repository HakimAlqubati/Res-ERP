<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Support;

use App\Models\Employee;
use App\Models\EmployeeSalaryLog;
use App\Modules\HR\SalaryIncrease\Contracts\EmployeeSalaryLogServiceInterface;
use App\Modules\HR\SalaryIncrease\Rules\SalaryChangeGuard;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Isolated SRP handler for EmployeeObserver salary lifecycle hooks.
 * Keeps `EmployeeObserver` minimal and free of salary-domain logic.
 */
final class EmployeeSalaryObserverHandler
{
    public const USER_SYNCED_FIELDS = [
        'name',
        'email',
        'phone_number',
        'branch_id',
        'manager_id',
        'gender',
        'active',
        'nationality',
        'employee_type',
        'avatar',
        'can_add_branch_order',
    ];

    public function __construct(
        private readonly EmployeeSalaryLogServiceInterface $salaryLogService,
        private readonly SalaryChangeGuard $salaryChangeGuard,
    ) {}

    public function handleCreated(Employee $employee): void
    {
        $this->salaryLogService->recordInitialSalary(
            employee: $employee,
            effectiveDate: $employee->join_date ? Carbon::parse($employee->join_date)->toDateString() : null,
            source: EmployeeSalaryLog::SOURCE_INITIAL,
            actorId: auth()->id(),
        );
    }

    /**
     * Safety net for direct programmatic salary edits with strict numeric equality check
     * to prevent `isDirty('salary')` false positives ("3000.00" vs 3000).
     */
    public function handleUpdating(Employee $employee): void
    {
        $employee->pendingSalaryLogChange = null;

        if ($employee->skipSalaryLogObserver || ! $employee->isDirty('salary')) {
            return;
        }

        $oldSalary = round((float) ($employee->getOriginal('salary') ?? 0), 2);
        $newSalary = round((float) ($employee->salary ?? 0), 2);

        if ($oldSalary === $newSalary) {
            return;
        }

        if ($newSalary < 0) {
            throw ValidationException::withMessages([
                'salary' => ['Salary cannot be negative.'],
            ]);
        }

        $effectiveDate = $employee->salaryEffectiveDate ?: now()->toDateString();

        $this->salaryChangeGuard->assertAllowedForPeriod(
            (int) $employee->id,
            $effectiveDate,
            'salary'
        );

        $employee->pendingSalaryLogChange = [
            'old_salary' => $oldSalary,
            'new_salary' => $newSalary,
            'effective_date' => Carbon::parse($effectiveDate)->toDateString(),
        ];
    }

    public function handleUpdated(Employee $employee): void
    {
        if ($employee->pendingSalaryLogChange === null || $employee->skipSalaryLogObserver) {
            return;
        }

        $change = $employee->pendingSalaryLogChange;
        $employee->pendingSalaryLogChange = null;
        $employee->salaryEffectiveDate = null;

        $effectiveFrom = Carbon::parse($change['effective_date'])->startOfMonth();

        $this->salaryLogService->recordSalaryChange(
            employee: $employee,
            newSalary: $change['new_salary'],
            effectiveDate: $change['effective_date'],
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            notes: 'Direct employee salary update.',
            actorId: auth()->id(),
            syncEmployeeColumn: false,
            baselineSalaryOverride: $change['old_salary'],
            allowSameSalary: true,
        );

        if ($effectiveFrom->gt(now()->startOfMonth())) {
            $employee->skipSalaryLogObserver = true;
            try {
                $employee->salary = $change['old_salary'];
                $employee->save();
            } finally {
                $employee->skipSalaryLogObserver = false;
            }
        }
    }

    public function shouldSkipUserSync(Employee $employee): bool
    {
        return ! $employee->wasChanged(self::USER_SYNCED_FIELDS);
    }
}
