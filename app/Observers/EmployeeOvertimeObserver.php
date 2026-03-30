<?php

namespace App\Observers;

use App\Exceptions\HR\PayrollConflictException;
use App\Models\EmployeeOvertime;
use App\Models\PayrollRun;
use Carbon\Carbon;

/**
 * Observer for EmployeeOvertime model.
 * 
 * Enforces business rules relating to payroll cycles:
 * - Prevents rolling back approved overtime if a payroll run for that period exists.
 * - Prevents deleting overtime records if a payroll run for that period exists.
 */
class EmployeeOvertimeObserver
{
    /**
     * Prevent rolling back (undoing approval) of overtime if payroll has been run.
     * 
     * @param EmployeeOvertime $employeeOvertime
     * @return void
     * @throws PayrollConflictException
     */
    public function updating(EmployeeOvertime $employeeOvertime): void
    {
        // Detect rollback: approved field transitioning from true to false
        if ($employeeOvertime->isDirty('approved') && !$employeeOvertime->approved && $employeeOvertime->getOriginal('approved')) {
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
