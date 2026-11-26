<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Employee;
use App\Models\EmployeeSalaryLog;
use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EmployeeSalaryLogObserver
{
    public function __construct(
        private readonly PayrollLockGuard $lockGuard,
        private readonly EmployeeSalaryResolverInterface $salaryResolver,
    ) {}

    public function creating(EmployeeSalaryLog $log): void
    {
        if ($log->effective_from) {
            $log->effective_from = EmployeeSalaryLog::normalizeEffectiveFrom($log->effective_from)->toDateString();
        }
    }

    public function updating(EmployeeSalaryLog $log): void
    {
        if ($log->effective_from) {
            $log->effective_from = EmployeeSalaryLog::normalizeEffectiveFrom($log->effective_from)->toDateString();
        }

        $originalRequestId = $log->getOriginal('salary_increase_request_id');
        if ($originalRequestId !== null && $this->isLinkedToApprovedRequest((int) $originalRequestId)) {
            throw ValidationException::withMessages([
                'salary_increase_request_id' => [
                    'Cannot modify a salary log linked to an approved salary increase request.',
                ],
            ]);
        }

        $originalEff = $log->getOriginal('effective_from');
        if ($originalEff) {
            $parsedOriginal = Carbon::parse($originalEff)->startOfMonth();
            $this->lockGuard->checkLockFromPeriod(
                (int) $log->employee_id,
                $parsedOriginal->year,
                $parsedOriginal->month,
                'effective_from'
            );
        }

        if ($log->effective_from) {
            $parsedNew = Carbon::parse($log->effective_from)->startOfMonth();
            $this->lockGuard->checkLockFromPeriod(
                (int) $log->employee_id,
                $parsedNew->year,
                $parsedNew->month,
                'effective_from'
            );
        }

        if (
            $log->isDirty(['effective_from', 'new_salary', 'old_salary'])
            && $this->hasLaterLog($log, $originalEff)
        ) {
            throw ValidationException::withMessages([
                'effective_from' => [
                    'Cannot modify a salary log when a later salary log already exists for this employee.',
                ],
            ]);
        }

        if ($log->isDirty('effective_from') && $log->effective_from) {
            $parsedNew = Carbon::parse($log->effective_from)->startOfMonth();
            $latestOther = EmployeeSalaryLog::query()
                ->where('employee_id', (int) $log->employee_id)
                ->where('id', '!=', (int) $log->id)
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first(['effective_from']);

            if ($latestOther !== null && $latestOther->effective_from) {
                $latestOtherMonth = Carbon::parse($latestOther->effective_from)->startOfMonth();
                if ($parsedNew->lt($latestOtherMonth)) {
                    throw ValidationException::withMessages([
                        'effective_from' => [
                            "Effective month ({$parsedNew->format('F Y')}) cannot be earlier than the previous salary log ({$latestOtherMonth->format('F Y')}).",
                        ],
                    ]);
                }
            }
        }

        if ($log->isDirty(['new_salary', 'old_salary'])) {
            $log->change_amount = $log->old_salary !== null
                ? round((float) $log->new_salary - (float) $log->old_salary, 2)
                : null;
        }
    }

    public function updated(EmployeeSalaryLog $log): void
    {
        if ($log->wasChanged(['effective_from', 'new_salary'])) {
            $this->syncEmployeeSalaryCache($log);
        }
    }

    public function deleting(EmployeeSalaryLog $log): void
    {
        if ($log->salary_increase_request_id !== null && $this->isLinkedToApprovedRequest((int) $log->salary_increase_request_id)) {
            throw ValidationException::withMessages([
                'salary_increase_request_id' => [
                    'Cannot delete a salary log linked to an approved salary increase request.',
                ],
            ]);
        }

        if ($this->hasLaterLog($log, $log->effective_from)) {
            throw ValidationException::withMessages([
                'effective_from' => [
                    'Cannot delete a salary log while a later salary log exists. Delete the latest salary log first.',
                ],
            ]);
        }

        if ($log->effective_from) {
            $parsed = Carbon::parse($log->effective_from)->startOfMonth();
            $this->lockGuard->checkLockFromPeriod(
                (int) $log->employee_id,
                $parsed->year,
                $parsed->month,
                'effective_from'
            );
        }
    }

    public function deleted(EmployeeSalaryLog $log): void
    {
        $this->syncEmployeeSalaryCache($log);
    }

    private function syncEmployeeSalaryCache(EmployeeSalaryLog $log): void
    {
        if (! $log->employee_id) {
            return;
        }

        $employee = Employee::withoutGlobalScopes()->find($log->employee_id);
        if (! $employee) {
            return;
        }

        // Only re-sync cache if at least one log remains; if all logs were removed, preserve existing salary.
        if (! EmployeeSalaryLog::query()->where('employee_id', $employee->id)->exists()) {
            return;
        }

        $now = now();
        $resolved = $this->salaryResolver->resolveForPeriod($employee, $now->year, $now->month);

        if (round((float) ($employee->salary ?? 0), 2) !== round($resolved, 2)) {
            $employee->skipSalaryLogObserver = true;
            try {
                $employee->salary = $resolved;
                $employee->save();
            } finally {
                $employee->skipSalaryLogObserver = false;
            }
        }
    }

    private function hasLaterLog(EmployeeSalaryLog $log, mixed $referenceEffectiveFrom): bool
    {
        if (! $log->employee_id || ! $referenceEffectiveFrom) {
            return false;
        }

        $refDate = EmployeeSalaryLog::normalizeEffectiveFrom($referenceEffectiveFrom)->toDateString();

        return EmployeeSalaryLog::query()
            ->where('employee_id', (int) $log->employee_id)
            ->where('id', '!=', (int) $log->id)
            ->where(function ($query) use ($refDate, $log): void {
                $query->where('effective_from', '>', $refDate)
                    ->orWhere(function ($sameMonth) use ($refDate, $log): void {
                        $sameMonth->where('effective_from', $refDate)
                            ->where('id', '>', (int) $log->id);
                    });
            })
            ->exists();
    }

    private function isLinkedToApprovedRequest(int $requestId): bool
    {
        return SalaryIncreaseRequest::withTrashed()
            ->where('id', $requestId)
            ->where('status', SalaryIncreaseRequest::STATUS_APPROVED)
            ->exists();
    }
}
