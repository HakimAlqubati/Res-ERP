<?php

namespace App\Observers;

use App\Models\EmployeeReward;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;

class EmployeeRewardObserver
{
    public function __construct(
        private readonly PayrollLockGuard $payrollLockGuard,
    ) {}

    /**
     * Handle the EmployeeReward "creating" event.
     * Sets default values before a new record is persisted.
     */
    public function creating(EmployeeReward $reward): void
    {
        if (auth()->check() && empty($reward->created_by)) {
            $reward->created_by = auth()->id();
        }

        if ($reward->branch_id == null) {
            $reward->branch_id = $reward->employee->branch_id;
        }
    }

    /**
     * Handle the EmployeeReward "saving" event.
     * Covers both creating and updating.
     */
    public function saving(EmployeeReward $reward): void
    {
        // 1. Ensure month and year are always synchronized with the date
        if ($reward->date) {
            $date = Carbon::parse($reward->date);
            // $reward->month = (int) $date->month;
            $reward->year = (int) $date->year;
        }

        // 2. Identify if we are attempting a locked operation:
        // - Creating a new record
        // - Approving a pending record
        // - Changing the period (date/month/year)
        $isApproving = $reward->isDirty('status') && $reward->status === EmployeeReward::STATUS_APPROVED;
        $isPeriodChanging = $reward->isDirty(['date', 'month', 'year']);
        $isNew = ! $reward->exists;

        if ($isNew || $isApproving || $isPeriodChanging) {
            $this->guard($reward);
        }

        // 3. Rollback protection: Prevent undoing approval if payroll is locked
        $isRollingBack = $reward->isDirty('status') &&
                         $reward->getOriginal('status') === EmployeeReward::STATUS_APPROVED &&
                         $reward->status !== EmployeeReward::STATUS_APPROVED;

        if ($isRollingBack) {
            $this->guard($reward);
        }
    }

    /**
     * Handle the EmployeeReward "deleting" event.
     */
    public function deleting(EmployeeReward $reward): void
    {
        $this->guard($reward);
    }

    /**
     * Centralized guard to check if the payroll period is locked.
     */
    private function guard(EmployeeReward $reward): void
    {
        if (empty($reward->year) || empty($reward->month) || empty($reward->employee_id)) {
            return;
        }

        $this->payrollLockGuard->checkLock(
            (int) $reward->employee_id,
            (int) $reward->year,
            (int) $reward->month,
            'date'
        );
    }
}
