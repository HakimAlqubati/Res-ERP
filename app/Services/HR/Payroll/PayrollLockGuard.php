<?php

namespace App\Services\HR\Payroll;

use App\Models\EmployeeApplicationV2;
use App\Models\Payroll;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Guards employee applications against being created after a payroll run
 * has already been finalised for the relevant month.
 *
 * This is a pure, stateless service — no side-effects, no persistence.
 */
final class PayrollLockGuard
{
    /**
     * Assert that no payroll has been processed for the employee in the
     * month/year that the application falls in.
     *
     * @throws \Illuminate\Validation\ValidationException  when the payroll period is already locked.
     */
    public function ensurePayrollNotLocked(EmployeeApplicationV2 $application): void
    {
        [$year, $month] = $this->resolvePeriod($application);

        $isLocked = $this->hasExistingPayroll($application->employee_id, $year, $month);

        if ($isLocked) {
            $period = $this->monthLabel($year, $month);
            throw ValidationException::withMessages([
                'application_date' => "Locked: Payroll for {$period} is already finalized. " .
                    "Cannot submit new requests for this employee in this period.",
            ]);
        }
    }

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    /**
     * Derive [year, month] from the application's date field,
     * falling back to today when the field is absent.
     *
     * @return array{int, int}
     */
    private function resolvePeriod(EmployeeApplicationV2 $application): array
    {
        $date = $application->application_date
            ? Carbon::parse($application->application_date)
            : Carbon::today();

        return [$date->year, $date->month];
    }

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
