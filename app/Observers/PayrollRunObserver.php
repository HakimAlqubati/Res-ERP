<?php

namespace App\Observers;

use App\Enums\HR\Payroll\SalaryTransactionSubType;
use App\Models\AdvanceRequest;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\PayrollRun;
use App\Services\Financial\PayrollFinancialSyncService;


/**
 * Observer for PayrollRun model.
 * 
 * Automatically syncs approved/completed payroll runs with the financial system.
 * Also marks advance installments as paid when payroll is approved.
 */
class PayrollRunObserver
{
    public function __construct(
        protected PayrollFinancialSyncService $syncService
    ) {}

    /**
     * Handle the PayrollRun "updated" event.
     * 
     * When status changes to approved or completed, sync with financial system.
     * When status changes to approved, mark advance installments as paid.
     */
    public function updated(PayrollRun $payrollRun): void
    {
        // Check if status was changed
        if (!$payrollRun->isDirty('status')) {
            return;
        }

        $newStatus = $payrollRun->status;
        $oldStatus = $payrollRun->getOriginal('status');

        // Only process when status changes TO approved or completed
        if (in_array($newStatus, [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_COMPLETED])) {
            // Don't re-process if already in one of these statuses
            if (in_array($oldStatus, [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_COMPLETED])) {
                return;
            }

            // Mark advance installments as paid when status becomes APPROVED
            if ($newStatus === PayrollRun::STATUS_APPROVED) {
                $this->markInstallmentsAsPaid($payrollRun);
            }

            // Sync with financial system
            try {
                $result = $this->syncService->syncPayrollRun($payrollRun->id);
            } catch (\Exception $e) {
                // Silent fail - error already handled by syncService
            }
        }
    }

    /**
     * Handle the PayrollRun "deleted" event.
     * 
     * Clean up financial transactions when payroll run is deleted.
     */
    public function deleted(PayrollRun $payrollRun): void
    {
        try {
            $this->syncService->deletePayrollRunTransactions($payrollRun->id);
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Mark all scheduled advance installments as paid for a PayrollRun.
     * Also updates the related AdvanceRequest remaining_total and paid_installments.
     * Handles both current period installments AND early installments added via SalaryTransaction.
     */
    protected function markInstallmentsAsPaid(PayrollRun $payrollRun): void
    {
        try {
            // Get all employee IDs in this payroll run
            $employeeIds = $payrollRun->payrolls()->pluck('employee_id')->toArray();

            if (empty($employeeIds)) {
                return;
            }

            // Get the period boundaries (for reference/logging)
            $periodStart = sprintf('%04d-%02d-01', $payrollRun->year, $payrollRun->month);
            $periodEnd = date('Y-m-t', strtotime($periodStart));

            // Find ALL installments linked via SalaryTransaction in this payroll run
            // This includes both:
            // - ADVANCE_INSTALLMENT: قسط الشهر الحالي
            // - EARLY_ADVANCE_INSTALLMENT: قسط مبكر (شهر قادم)
            $installmentIds = \App\Models\SalaryTransaction::query()
                ->where('payroll_run_id', $payrollRun->id)
                ->where('reference_type', EmployeeAdvanceInstallment::class)
                ->whereIn('sub_type', [
                    SalaryTransactionSubType::ADVANCE_INSTALLMENT->value,
                    SalaryTransactionSubType::EARLY_ADVANCE_INSTALLMENT->value,
                ])
                ->pluck('reference_id')
                ->filter() // remove nulls
                ->unique()
                ->toArray();

            $allInstallments = EmployeeAdvanceInstallment::query()
                ->whereIn('id', $installmentIds)
                ->where('is_paid', false)
                ->get();

            if ($allInstallments->isEmpty()) {
                return;
            }

            // Group installments by advance_request_id to update AdvanceRequest later
            $advanceRequestUpdates = [];

            foreach ($allInstallments as $installment) {
                // Find the payroll for this employee in this run
                $payroll = $payrollRun->payrolls()
                    ->where('employee_id', $installment->employee_id)
                    ->first();

                // Mark installment as paid
                $installment->markPaid(
                    payrollId: $payroll?->id,
                    paidById: $payrollRun->approved_by,
                    paymentMethod: EmployeeAdvanceInstallment::PAYMENT_METHOD_PAYROLL,
                    when: $payrollRun->approved_at ?? now()
                );

                // Collect advance_request_id for batch update
                if ($installment->advance_request_id) {
                    if (!isset($advanceRequestUpdates[$installment->advance_request_id])) {
                        $advanceRequestUpdates[$installment->advance_request_id] = [
                            'count' => 0,
                            'amount' => 0.0,
                        ];
                    }
                    $advanceRequestUpdates[$installment->advance_request_id]['count']++;
                    $advanceRequestUpdates[$installment->advance_request_id]['amount'] += (float) $installment->installment_amount;
                }
            }

            // Update AdvanceRequest remaining_total and paid_installments
            foreach ($advanceRequestUpdates as $advanceRequestId => $data) {
                $advanceRequest = AdvanceRequest::find($advanceRequestId);
                if ($advanceRequest) {
                    $advanceRequest->increment('paid_installments', $data['count']);
                    $advanceRequest->decrement('remaining_total', $data['amount']);

                    // Ensure remaining_total doesn't go below 0
                    if ($advanceRequest->remaining_total < 0) {
                        $advanceRequest->update(['remaining_total' => 0]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
