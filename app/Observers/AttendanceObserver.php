<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;

/**
 * Observer for the Attendance model.
 *
 * Enforces payroll lock rules:
 * - Prevents creating attendance records in a finalized payroll period.
 * - Prevents modifying attendance records whose check_date falls in a finalized period.
 * - Prevents deleting attendance records in a finalized payroll period.
 */
class AttendanceObserver
{
    public function __construct(
        private readonly PayrollLockGuard $payrollLockGuard,
    ) {}

    /**
     * Block creating attendance if the payroll period is already finalized.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function creating(Attendance $attendance): void
    {
        $this->guardPeriod($attendance);
    }

   

    /**
     * Block deleting attendance if the payroll period is already finalized.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function deleting(Attendance $attendance): void
    {
        $this->guardPeriod($attendance);
    }

    /**
     * Handle the Attendance "deleted" event.
     * After deletion, if the record type is checkin, delete the associated checkout record.
     */
    public function deleted(Attendance $attendance): void
    {
        if ($attendance->check_type === Attendance::CHECKTYPE_CHECKIN) {
            $attendance->checkout?->delete();
        }
    }

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the relevant date and delegate the lock check to PayrollLockGuard.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function guardPeriod(Attendance $attendance): void
    {
        $rawDate = $attendance->check_date;

        if (empty($rawDate) || empty($attendance->employee_id)) {
            return;
        }

        $date = Carbon::parse($rawDate);

        $this->payrollLockGuard->checkLock(
            (int) $attendance->employee_id,
            $date->year,
            $date->month,
            'date'
        );
    }
}
