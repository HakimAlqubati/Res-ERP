<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Rules;

use App\Models\EmployeeSalaryLog;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Single Source of Truth guard for validating whether a salary change is permitted
 * for a given employee and effective date:
 *  1. Forward payroll lock check (no active payroll in target month or any later month).
 *  2. Month-level chronological ordering against the latest recorded salary log.
 */
final class SalaryChangeGuard
{
    public function __construct(
        private readonly PayrollLockGuard $lockGuard,
    ) {}

    /**
     * Assert both forward payroll lock and chronological month ordering,
     * returning the normalized `effective_from` month start (`YYYY-MM-01`).
     *
     * @throws ValidationException
     */
    public function assertAllowedForPeriod(
        int $employeeId,
        Carbon|string $effectiveDate,
        string $errorField = 'effective_date'
    ): Carbon {
        $effectiveFrom = EmployeeSalaryLog::normalizeEffectiveFrom($effectiveDate);

        $this->lockGuard->checkLockFromPeriod(
            $employeeId,
            $effectiveFrom->year,
            $effectiveFrom->month,
            $errorField
        );

        $this->assertNotEarlierThanLatestLog($employeeId, $effectiveFrom, $errorField);

        return $effectiveFrom;
    }

    /**
     * @throws ValidationException
     */
    private function assertNotEarlierThanLatestLog(int $employeeId, Carbon $effectiveFrom, string $errorField): void
    {
        $latestLog = EmployeeSalaryLog::query()
            ->where('employee_id', $employeeId)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first(['effective_from']);

        if ($latestLog === null || ! $latestLog->effective_from) {
            return;
        }

        $latestMonth = Carbon::parse($latestLog->effective_from)->startOfMonth();

        if ($effectiveFrom->lt($latestMonth)) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Effective month ({$effectiveFrom->format('F Y')}) cannot be earlier than the latest recorded salary change ({$latestMonth->format('F Y')}).",
                ],
            ]);
        }
    }
}
