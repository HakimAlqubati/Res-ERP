<?php

namespace App\Observers;

use App\Models\AdvanceWage;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Enums\FinancialCategoryCode;
use App\Modules\HR\Payroll\Contracts\PayrollSimulatorInterface;
use App\Services\HR\Payroll\PayrollLockGuard;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
        $this->syncToFinancial($advanceWage);
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
        $this->syncToFinancial($advanceWage);
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
        try {
            FinancialTransaction::where('reference_type', AdvanceWage::class)
                ->where('reference_id', $advanceWage->id)
                ->delete();
        } catch (\Exception $e) {
            Log::error("Failed to delete financial transaction for AdvanceWage #{$advanceWage->id}: " . $e->getMessage());
        }
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

    /**
     * Synchronize the AdvanceWage with the financial transactions table.
     */
    private function syncToFinancial(AdvanceWage $advanceWage): void
    {
        try {
            // Only sync settled advance wages
            if ($advanceWage->status !== AdvanceWage::STATUS_SETTLED) {
                // If it was settled before and now it's not, we might want to delete the transaction
                FinancialTransaction::where('reference_type', AdvanceWage::class)
                    ->where('reference_id', $advanceWage->id)
                    ->delete();
                return;
            }

            $salaryCategory = FinancialCategory::findByCode(FinancialCategoryCode::PAYROLL_SALARIES);

            if (!$salaryCategory) {
                Log::warning("Financial category for salaries (" . FinancialCategoryCode::PAYROLL_SALARIES . ") not found during AdvanceWage sync.");
                return;
            }

            $transactionDate = Carbon::create($advanceWage->year, $advanceWage->month, 1);

            FinancialTransaction::updateOrCreate(
                [
                    'reference_type' => AdvanceWage::class,
                    'reference_id'   => $advanceWage->id,
                ],
                [
                    'branch_id'        => $advanceWage->branch_id,
                    'category_id'      => $salaryCategory->id,
                    'amount'           => $advanceWage->amount,
                    'type'             => FinancialTransaction::TYPE_EXPENSE,
                    'transaction_date' => $transactionDate,
                    'status'           => FinancialTransaction::STATUS_PAID,
                    'description'      => __('Advance Wage') . ': ' . ($advanceWage->employee?->name ?? 'Employee #' . $advanceWage->employee_id) . " ({$advanceWage->year}/{$advanceWage->month})",
                    'created_by'       => auth()->id() ?? $advanceWage->created_by ?? 1,
                    'month'            => $advanceWage->month,
                    'year'             => $advanceWage->year,
                ]
            );
        } catch (\Exception $e) {
            Log::error("Failed to sync AdvanceWage #{$advanceWage->id} to financial transactions: " . $e->getMessage());
        }
    }
}
