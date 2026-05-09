<?php

namespace App\Observers;

use App\Models\EmployeePeriod;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;

class EmployeePeriodObserver
{
    public function __construct(
        private readonly PayrollLockGuard $payrollLockGuard
    ) {}

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function creating(EmployeePeriod $period): void
    {
        $this->checkLock($period);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updating(EmployeePeriod $period): void
    {
        // Check lock for the original start date to prevent modifying a locked period
        if ($period->getOriginal('start_date')) {
            $date = Carbon::parse($period->getOriginal('start_date'));
            $this->payrollLockGuard->checkLock(
                $period->employee_id,
                $date->year,
                $date->month,
                'start_date'
            );
        }

        // Check lock for the new start date to prevent moving into a locked period
        $this->checkLock($period);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function deleting(EmployeePeriod $period): void
    {
        $this->checkLock($period);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    private function checkLock(EmployeePeriod $period): void
    {

        return;
        if (! $period->start_date) {
            return;
        }

        $date = Carbon::parse($period->start_date);

        $this->payrollLockGuard->checkLock(
            $period->employee_id,
            $date->year,
            $date->month,
            'start_date'
        );
    }

    /**
     * Prevent modification of the period if the employee has existing attendance records.
     *
     * @param EmployeePeriod $period
     * @throws \Illuminate\Validation\ValidationException
     */
    private function preventModificationIfAttendanceExists(EmployeePeriod $period): void
    {
        if (!$period->employee_id || !$period->start_date) {
            return;
        }

        $attendanceExists = \App\Models\Attendance::where('employee_id', $period->employee_id)
            ->where('check_date', $period->start_date)
            ->exists();

        if ($attendanceExists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'start_date' => "Locked: Attendance records already exist for this employee on {$period->start_date}. " .
                    "You cannot create or modify a shift for a day that has existing attendance logs.",
            ]);
        }
    }
}
