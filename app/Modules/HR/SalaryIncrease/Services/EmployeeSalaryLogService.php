<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Services;

use App\Models\AppLog;
use App\Models\Employee;
use App\Models\EmployeeSalaryLog;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use App\Modules\HR\SalaryIncrease\Builders\EmployeeSalaryLogPayloadBuilder;
use App\Modules\HR\SalaryIncrease\Contracts\EmployeeSalaryLogServiceInterface;
use App\Modules\HR\SalaryIncrease\Rules\SalaryChangeGuard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EmployeeSalaryLogService implements EmployeeSalaryLogServiceInterface
{
    public function __construct(
        private readonly EmployeeSalaryResolverInterface $salaryResolver,
        private readonly SalaryChangeGuard $salaryChangeGuard,
    ) {}

    public function recordInitialSalary(
        Employee $employee,
        ?string $effectiveDate = null,
        string $source = EmployeeSalaryLog::SOURCE_INITIAL,
        ?int $actorId = null
    ): ?EmployeeSalaryLog {
        return DB::transaction(function () use ($employee, $effectiveDate, $source, $actorId): ?EmployeeSalaryLog {
            $lockedEmployee = Employee::withoutGlobalScopes()->lockForUpdate()->find($employee->id);
            if (! $lockedEmployee) {
                return null;
            }

            $salary = $lockedEmployee->salary !== null ? round((float) $lockedEmployee->salary, 2) : null;
            if ($salary === null || $salary <= 0) {
                return null;
            }

            if (EmployeeSalaryLog::query()->where('employee_id', $lockedEmployee->id)->exists()) {
                return null;
            }

            $dateSource = $effectiveDate
                ?: ($lockedEmployee->join_date ? Carbon::parse($lockedEmployee->join_date)->toDateString() : now()->toDateString());

            $payload = EmployeeSalaryLogPayloadBuilder::forEmployee((int) $lockedEmployee->id)
                ->toNewSalary($salary)
                ->effectiveOn($dateSource)
                ->fromSource($source)
                ->byActor($actorId ?? auth()->id())
                ->build();

            return EmployeeSalaryLog::create($payload);
        });
    }

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
    ): EmployeeSalaryLog {
        $normalizedNewSalary = round($newSalary, 2);
        if ($normalizedNewSalary < 0) {
            throw ValidationException::withMessages([
                'new_salary' => ['Salary cannot be negative.'],
            ]);
        }

        return DB::transaction(function () use (
            $employee,
            $normalizedNewSalary,
            $effectiveDate,
            $source,
            $requestId,
            $notes,
            $actorId,
            $syncEmployeeColumn,
            $baselineSalaryOverride,
            $allowSameSalary
        ): EmployeeSalaryLog {
            /** @var Employee $lockedEmployee */
            $lockedEmployee = Employee::withoutGlobalScopes()->lockForUpdate()->findOrFail($employee->id);

            $effectiveFrom = $this->salaryChangeGuard->assertAllowedForPeriod(
                (int) $lockedEmployee->id,
                $effectiveDate,
                'effective_date'
            );

            $resolvedActorId = $actorId ?? auth()->id();

            $this->ensureBaselineExists($lockedEmployee, $effectiveFrom, $baselineSalaryOverride, $resolvedActorId);

            $oldSalary = round(
                $this->salaryResolver->resolveForPeriod($lockedEmployee, $effectiveFrom->year, $effectiveFrom->month),
                2
            );

            if (! $allowSameSalary && $oldSalary === $normalizedNewSalary) {
                throw ValidationException::withMessages([
                    'new_salary' => ['The new salary must differ from the effective salary for the selected period.'],
                ]);
            }

            $log = EmployeeSalaryLog::create(
                EmployeeSalaryLogPayloadBuilder::forEmployee((int) $lockedEmployee->id)
                    ->fromOldSalary($oldSalary)
                    ->toNewSalary($normalizedNewSalary)
                    ->effectiveOn($effectiveDate, $effectiveFrom)
                    ->fromSource($source)
                    ->linkedToRequest($requestId)
                    ->withNotes($notes)
                    ->byActor($resolvedActorId)
                    ->build()
            );

            if ($syncEmployeeColumn && $effectiveFrom->lte(now()->startOfMonth())) {
                $this->syncCachedEmployeeSalary($lockedEmployee, now()->startOfMonth());
            }

            return $log;
        });
    }

    public function syncCurrentEffectiveSalaries(?Carbon $asOf = null): array
    {
        $targetMonth = ($asOf ?? now())->copy()->startOfMonth();
        $targetDateStr = $targetMonth->toDateString();

        $rankedSubquery = DB::table('hr_employee_salary_logs')
            ->select([
                'employee_id',
                'new_salary',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY effective_from DESC, id DESC) as rn'),
            ])
            ->where('effective_from', '<=', $targetDateStr);

        $candidates = DB::query()
            ->fromSub($rankedSubquery, 'ranked')
            ->join('hr_employees', 'hr_employees.id', '=', 'ranked.employee_id')
            ->where('ranked.rn', 1)
            ->whereNull('hr_employees.deleted_at')
            ->whereRaw('COALESCE(hr_employees.salary, -1) <> ranked.new_salary')
            ->get(['ranked.employee_id']);

        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($candidates as $candidate) {
            $employeeId = (int) $candidate->employee_id;

            try {
                $didSync = DB::transaction(function () use ($employeeId, $targetMonth): bool {
                    $lockedEmployee = Employee::withoutGlobalScopes()->lockForUpdate()->find($employeeId);

                    return $lockedEmployee !== null && $this->syncCachedEmployeeSalary($lockedEmployee, $targetMonth);
                });

                if ($didSync) {
                    $synced++;
                }
            } catch (Throwable $e) {
                $failed++;
                $errors[] = ['employee_id' => $employeeId, 'message' => $e->getMessage()];

                AppLog::write(
                    message: "Failed to sync effective salary for employee #{$employeeId} as of {$targetDateStr}: {$e->getMessage()}",
                    level: AppLog::LEVEL_ERROR,
                    context: 'HR_SYNC_EFFECTIVE_SALARIES',
                    extra: ['employee_id' => $employeeId, 'target_month' => $targetDateStr, 'exception' => get_class($e)]
                );
            }
        }

        return [
            'checked' => $candidates->count(),
            'synced' => $synced,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Auto-Baseline Guard: if an employee has no prior logs (e.g. created via withoutEvents),
     * seed an initial baseline snapshot at min(join_date_month, effectiveFrom - 1 month).
     */
    private function ensureBaselineExists(
        Employee $lockedEmployee,
        Carbon $effectiveFrom,
        ?float $baselineSalaryOverride,
        ?int $actorId
    ): void {
        if (EmployeeSalaryLog::query()->where('employee_id', $lockedEmployee->id)->exists()) {
            return;
        }

        $baselineSalary = round($baselineSalaryOverride ?? (float) ($lockedEmployee->salary ?? 0), 2);
        $prevMonth = $effectiveFrom->copy()->subMonth()->startOfMonth();
        $joinDate = $lockedEmployee->join_date ? Carbon::parse($lockedEmployee->join_date) : null;
        $joinMonth = $joinDate?->copy()->startOfMonth();
        $useJoinDate = $joinMonth !== null && $joinMonth->lte($prevMonth);

        $baselineMonth = $useJoinDate ? $joinMonth : $prevMonth;
        $baselineDate = $useJoinDate
            ? $joinDate->toDateString()
            : $baselineMonth->toDateString();

        EmployeeSalaryLog::create(
            EmployeeSalaryLogPayloadBuilder::forEmployee((int) $lockedEmployee->id)
                ->toNewSalary($baselineSalary)
                ->effectiveOn($baselineDate, $baselineMonth)
                ->fromSource(EmployeeSalaryLog::SOURCE_AUTO_BASELINE)
                ->withNotes('Auto-generated baseline salary snapshot.')
                ->byActor($actorId)
                ->build()
        );
    }

    /**
     * Synchronize `hr_employees.salary` cache with the effective salary for `$targetMonth`
     * while preserving `OwenIt\Auditing` and suppressing `EmployeeObserver` re-logging.
     */
    private function syncCachedEmployeeSalary(Employee $lockedEmployee, Carbon $targetMonth): bool
    {
        $effective = round(
            $this->salaryResolver->resolveForPeriod($lockedEmployee, $targetMonth->year, $targetMonth->month),
            2
        );

        if (round((float) ($lockedEmployee->salary ?? 0), 2) === $effective) {
            return false;
        }

        $lockedEmployee->skipSalaryLogObserver = true;
        try {
            $lockedEmployee->salary = $effective;
            $lockedEmployee->save();
        } finally {
            $lockedEmployee->skipSalaryLogObserver = false;
        }

        return true;
    }
}
