<?php

namespace App\Observers;

use App\Models\PenaltyDeduction;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;

class PenaltyDeductionObserver
{
    public function __construct(
        private readonly PayrollLockGuard $payrollLockGuard,
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
            $penaltyDeduction->year  = (int) $date->year;
        }

        // 2. Identify if we are attempting a locked operation:
        // - Creating a new record
        // - Approving a pending record
        // - Changing the period (date/month/year)
        $isApproving = $penaltyDeduction->isDirty('status') && $penaltyDeduction->status === PenaltyDeduction::STATUS_APPROVED;
        $isPeriodChanging = $penaltyDeduction->isDirty(['date', 'month', 'year']);
        $isNew = !$penaltyDeduction->exists;

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
