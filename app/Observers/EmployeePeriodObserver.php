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
}
