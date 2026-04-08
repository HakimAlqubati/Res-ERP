<?php

namespace App\Observers;

use App\Models\AdvanceWage;
use App\Modules\HR\Payroll\Contracts\PayrollSimulatorInterface;
use App\Services\HR\Payroll\PayrollLockGuard;
use Illuminate\Validation\ValidationException;

class AdvanceWageObserver
{
    public function __construct(
        private readonly PayrollLockGuard $payrollLockGuard,
    ) {}

    /**
     * Handle the AdvanceWage "creating" event.
     */
    public function creating(AdvanceWage $advanceWage): void
    {
        // Set default status to settled if not provided
        if (!$advanceWage->status) {
            $advanceWage->status = AdvanceWage::STATUS_SETTLED;
        }

        // Set the creator
        if (!$advanceWage->created_by) {
            $advanceWage->created_by = \Illuminate\Support\Facades\Auth::id();
        }

        // Guard against finalized payroll periods
        $this->guardPeriod($advanceWage);

        // Validate amount against net salary
        $simulator = app(PayrollSimulatorInterface::class);
        $results = $simulator->simulateForEmployees([$advanceWage->employee_id], (int) $advanceWage->year, (int) $advanceWage->month);
        
        $netSalary = (float) ($results[0]['data']['net_salary'] ?? 0);
        
        if ((float)$advanceWage->amount > $netSalary) {
            throw ValidationException::withMessages([
                'amount' => __('The amount exceeds the employee\'s net salary for this period (:amount).', ['amount' => formatMoneyWithCurrency($netSalary)]),
            ]);
        }
    }

    /**
     * Handle the AdvanceWage "created" event.
     */
    public function created(AdvanceWage $advanceWage): void
    {
        //
    }

    /**
     * Handle the AdvanceWage "updated" event.
     */
    public function updating(AdvanceWage $advanceWage): void
    {
        $this->guardPeriod($advanceWage);
    }

    /**
     * Handle the AdvanceWage "updating" event.
     */
    public function updated(AdvanceWage $advanceWage): void
    {
        //
    }

    /**
     * Handle the AdvanceWage "deleting" event.
     */
    public function deleting(AdvanceWage $advanceWage): void
    {
        $this->guardPeriod($advanceWage);
    }

    /**
     * Handle the AdvanceWage "deleted" event.
     */
    public function deleted(AdvanceWage $advanceWage): void
    {
        //
    }

    /**
     * Handle the AdvanceWage "restored" event.
     */
    public function restored(AdvanceWage $advanceWage): void
    {
        //
    }

    /**
     * Handle the AdvanceWage "force deleted" event.
     */
    public function forceDeleted(AdvanceWage $advanceWage): void
    {
        //
    }

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the relevant date and delegate the lock check to PayrollLockGuard.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function guardPeriod(AdvanceWage $advanceWage): void
    {
        if (empty($advanceWage->year) || empty($advanceWage->month) || empty($advanceWage->employee_id)) {
            return;
        }

        $this->payrollLockGuard->checkLock(
            (int) $advanceWage->employee_id,
            (int) $advanceWage->year,
            (int) $advanceWage->month,
            'amount'
        );
    }
}
