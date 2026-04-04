<?php

namespace App\Services\HR\Payroll;

use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Guards any HR operation against being performed after a payroll run
 * has already been finalised for the relevant month.
 *
 * This is a pure, stateless service — no side-effects, no persistence.
 * Use checkLock(employeeId, year, month, errorField) from any context: 
 * applications, attendance, overtime, recalculations, etc.
 */
final class PayrollLockGuard
{
    /**
     * Assert that no payroll has been processed for the given employee
     * in the specified month/year.
     *
     * Callers are responsible for resolving the year/month from their
     * own context (application date, attendance date, overtime date, etc.).
     *
     * @param int $employeeId The ID of the employee.
     * @param int $year The year of the operation.
     * @param int $month The month of the operation.
     * @param string $errorField The validation error key to use if locked (default is 'date').
     *
     * @throws \Illuminate\Validation\ValidationException  when the payroll period is already locked.
     */
    public function checkLock(int $employeeId, int $year, int $month, string $errorField = 'date'): void
    {
        if ($this->hasExistingPayroll($employeeId, $year, $month)) {
            $period = $this->monthLabel($year, $month);

            throw ValidationException::withMessages([
                $errorField => "Locked: Payroll for {$period} is already finalized. " .
                    "Cannot process records for this employee in this period.",
            ]);
        }
    }

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    /**
     * Check whether an active Payroll record exists for the given employee
     * in the specified year/month.
     *
     * We deliberately exclude cancelled payrolls so that a cancelled run
     * does NOT block re-submission.
     */
    private function hasExistingPayroll(int $employeeId, int $year, int $month): bool
    {
        return Payroll::query()
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->where('month', $month)
            ->whereNotIn('status', [Payroll::STATUS_CANCELLED])
            ->exists();
    }

    /**
     * Human-readable "Month Year" label for error messages.
     */
    private function monthLabel(int $year, int $month): string
    {
        return Carbon::createFromDate($year, $month, 1)->format('F Y');
    }
}
