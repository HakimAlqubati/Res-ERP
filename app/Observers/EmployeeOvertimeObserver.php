<?php

namespace App\Observers;

use App\Exceptions\HR\PayrollConflictException;
use App\Models\EmployeeOvertime;
use App\Models\PayrollRun;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;

/**
 * Observer for EmployeeOvertime model.
 *
 * Enforces business rules relating to payroll cycles:
 * - Prevents approving overtime if the payroll period is already finalized.
 * - Prevents rolling back approved overtime if a payroll run for that period exists.
 * - Prevents deleting overtime records if a payroll run for that period exists.
 */
class EmployeeOvertimeObserver
{
    public function __construct(
        private readonly PayrollLockGuard $payrollLockGuard,
    ) {}


    /**
     * @throws \Exception
     */
    public function saving(EmployeeOvertime $employeeOvertime): void
    {
        if (!$employeeOvertime->exists) {
            return;
        }

        // 1. Prevent redundant status assignments
        // Check if status is being explicitly assigned to its current value
        // Note: Eloquent won't flag 'status' as dirty if assigned the same value, 
        // yet we catch it if someone forces a save with the same state in business logic situations.
        $newStatus = $employeeOvertime->status;
        $oldStatus = $employeeOvertime->getOriginal('status');

        // Check if they are trying to "re-set" the same state
        if ($newStatus === $oldStatus && request()->has('status')) {
            $msg = match ($newStatus) {
                EmployeeOvertime::STATUS_APPROVED => "Overtime already approved.",
                EmployeeOvertime::STATUS_REJECTED => "Overtime already rejected.",
                EmployeeOvertime::STATUS_PENDING  => "Overtime already pending.",
                default => null
            };

            if ($msg) {
                throw new \Exception($msg, 422);
            }
        }
    }

    /**
     * Prevent rolling back (undoing approval) of overtime if payroll has been run.
     * Also prevents updating approved records unless rolling back.
     */
    public function updating(EmployeeOvertime $employeeOvertime): void
    {
        $oldStatus = $employeeOvertime->getOriginal('status');
        $newStatus = $employeeOvertime->status;

        // 2. Protect Approved records from any modifications (hours, reason, etc.)
        if ($oldStatus === EmployeeOvertime::STATUS_APPROVED) {
            // Specific check for Rejecting an Approved record
            if ($newStatus === EmployeeOvertime::STATUS_REJECTED) {
                throw new \Exception("Cannot reject approved overtime.", 422);
            }

            // Only allow transition to PENDING (rollback)
            if ($newStatus !== EmployeeOvertime::STATUS_PENDING) {
                throw new \Exception("Overtime already approved.", 422);
            }
        }

        // 3. Block approval if the payroll period is already finalized for this employee.
        if (
            $employeeOvertime->isDirty('status') &&
            $newStatus === EmployeeOvertime::STATUS_APPROVED &&
            $oldStatus !== EmployeeOvertime::STATUS_APPROVED &&
            !empty($employeeOvertime->date)
        ) {
            $date = Carbon::parse($employeeOvertime->date);

            $this->payrollLockGuard->checkLock(
                (int) $employeeOvertime->employee_id,
                $date->year,
                $date->month,
                'date'
            );
        }

        // 4. Detect rollback: status field transitioning from approved to something else.
        if (
            $employeeOvertime->isDirty('status') &&
            $newStatus !== EmployeeOvertime::STATUS_APPROVED &&
            $oldStatus === EmployeeOvertime::STATUS_APPROVED
        ) {
            $this->ensureNoConflictWithPayroll($employeeOvertime);
        }
    }

    /**
     * Prevent deletion of overtime records if payroll has been run.
     * 
     * @param EmployeeOvertime $employeeOvertime
     * @return void
     * @throws PayrollConflictException
     */
    public function deleting(EmployeeOvertime $employeeOvertime): void
    {
        $this->ensureNoConflictWithPayroll($employeeOvertime);
    }

    /**
     * Core validation logic to find existing PayrollRuns for the overtime period.
     * 
     * @param EmployeeOvertime $employeeOvertime
     * @return void
     * @throws PayrollConflictException
     */
    private function ensureNoConflictWithPayroll(EmployeeOvertime $employeeOvertime): void
    {
        if (empty($employeeOvertime->date)) {
            return;
        }

        $date = Carbon::parse($employeeOvertime->date);

        $conflictExists = PayrollRun::query()
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->where('branch_id', $employeeOvertime->branch_id)
            ->exists();

        if ($conflictExists) {
            throw PayrollConflictException::overtimeLockedByPayroll();
        }
    }

    /**
     * Handle the EmployeeOvertime "created" event.
     */
    public function created(EmployeeOvertime $employeeOvertime): void
    {
        //
    }

    /**
     * Handle the EmployeeOvertime "updated" event.
     */
    public function updated(EmployeeOvertime $employeeOvertime): void
    {
        //
    }

    /**
     * Handle the EmployeeOvertime "deleted" event.
     */
    public function deleted(EmployeeOvertime $employeeOvertime): void
    {
        //
    }

    /**
     * Handle the EmployeeOvertime "restored" event.
     */
    public function restored(EmployeeOvertime $employeeOvertime): void
    {
        //
    }

    /**
     * Handle the EmployeeOvertime "force deleted" event.
     */
    public function forceDeleted(EmployeeOvertime $employeeOvertime): void
    {
        //
    }
}
