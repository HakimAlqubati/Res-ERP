<?php

namespace App\Observers;

use App\Models\PenaltyDeduction;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;

class PenaltyDeductionObserver
{
    public function __construct(
        private readonly PayrollLockGuard $payrollLockGuard,
        private readonly EmployeeSalaryResolverInterface $salaryResolver,
    ) {}

    /**
     * Handle the PenaltyDeduction "saving" event.
     * Covers both creating and updating.
     */
    public function saving(PenaltyDeduction $penaltyDeduction): void
    {
        // 1. Ensure month and year are always synchronized with the date
        if ($penaltyDeduction->date) {
            $date = Carbon::parse($penaltyDeduction->date);
            $penaltyDeduction->month = (int) $date->month;
            $penaltyDeduction->year = (int) $date->year;
        }

        // 1b. Enforce period-accurate percentage penalty calculation from hr_employee_salary_logs
        if (
            $penaltyDeduction->deduction_type === PenaltyDeduction::DEDUCTION_TYPE_SPECIFIC_PERCENTAGE
            && $penaltyDeduction->employee_id
            && $penaltyDeduction->year
            && $penaltyDeduction->month
            && (float) ($penaltyDeduction->percentage ?? 0) > 0
            && (
                ! $penaltyDeduction->exists
                || $penaltyDeduction->isDirty(['date', 'month', 'year', 'employee_id', 'percentage', 'deduction_type'])
            )
        ) {
            $effectiveSalary = $this->salaryResolver->resolveForPeriod(
                (int) $penaltyDeduction->employee_id,
                (int) $penaltyDeduction->year,
                (int) $penaltyDeduction->month
            );
            $penaltyDeduction->penalty_amount = round(($effectiveSalary * (float) $penaltyDeduction->percentage) / 100, 2);
        }

        // 2. Identify if we are attempting a locked operation:
        // - Creating a new record
        // - Approving a pending record
        // - Changing the period (date/month/year)
        $isApproving = $penaltyDeduction->isDirty('status') && $penaltyDeduction->status === PenaltyDeduction::STATUS_APPROVED;
        $isPeriodChanging = $penaltyDeduction->isDirty(['date', 'month', 'year']);
        $isNew = ! $penaltyDeduction->exists;

        if ($isNew || $isApproving || $isPeriodChanging) {
            $this->guard($penaltyDeduction);
        }

        // 3. Rollback protection: Prevent undoing approval if payroll is locked
        $isRollingBack = $penaltyDeduction->isDirty('status') &&
                         $penaltyDeduction->getOriginal('status') === PenaltyDeduction::STATUS_APPROVED &&
                         $penaltyDeduction->status !== PenaltyDeduction::STATUS_APPROVED;

        if ($isRollingBack) {
            $this->guard($penaltyDeduction);
        }
    }

    /**
     * Handle the PenaltyDeduction "deleting" event.
     */
    public function deleting(PenaltyDeduction $penaltyDeduction): void
    {
        $this->guard($penaltyDeduction);
    }

    /**
     * Centralized guard to check if the payroll period is locked.
     */
    private function guard(PenaltyDeduction $penaltyDeduction): void
    {
        if (empty($penaltyDeduction->year) || empty($penaltyDeduction->month) || empty($penaltyDeduction->employee_id)) {
            return;
        }

        $this->payrollLockGuard->checkLock(
            (int) $penaltyDeduction->employee_id,
            (int) $penaltyDeduction->year,
            (int) $penaltyDeduction->month,
            'date'
        );
    }
}
