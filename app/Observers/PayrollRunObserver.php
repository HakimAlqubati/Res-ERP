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
            // 1. إرجاع أقساط السلف إلى غير مدفوعة
            $this->revertInstallmentsToUnpaid($payrollRun);

            // 2. حذف المعاملات المالية
            $this->syncService->deletePayrollRunTransactions($payrollRun->id);

            // 3. حذف حركات الراتب
            $payrollRun->transactions()->delete();

            // 4. حذف كشوفات الرواتب
            $payrollRun->payrolls()->delete();
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Handle the PayrollRun "forceDeleted" event.
     * 
     * Permanently delete all related data when payroll run is force deleted.
     */
    public function forceDeleted(PayrollRun $payrollRun): void
    {
        try {
            // 1. حذف المعاملات المالية نهائياً
            $this->syncService->deletePayrollRunTransactions($payrollRun->id);

            // 2. حذف حركات الراتب نهائياً (بما في ذلك المحذوفة مسبقاً)
            $payrollRun->transactions()->withTrashed()->forceDelete();

            // 3. حذف كشوفات الرواتب نهائياً (بما في ذلك المحذوفة مسبقاً)
            $payrollRun->payrolls()->withTrashed()->forceDelete();
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Handle the PayrollRun "restored" event.
     * 
     * Restore all related data when payroll run is restored.
     */
    public function restored(PayrollRun $payrollRun): void
    {
        try {
            // 1. استعادة حركات الراتب
            $payrollRun->transactions()->onlyTrashed()->restore();

            // 2. استعادة كشوفات الرواتب
            $payrollRun->payrolls()->onlyTrashed()->restore();
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

    /**
     * Revert all paid installments back to unpaid when PayrollRun is deleted.
     * This is the reverse of markInstallmentsAsPaid().
     */
    protected function revertInstallmentsToUnpaid(PayrollRun $payrollRun): void
    {
        try {
            // Find ALL installments linked via SalaryTransaction in this payroll run
            $installmentIds = \App\Models\SalaryTransaction::query()
                ->where('payroll_run_id', $payrollRun->id)
                ->where('reference_type', EmployeeAdvanceInstallment::class)
                ->whereIn('sub_type', [
                    SalaryTransactionSubType::ADVANCE_INSTALLMENT->value,
                    SalaryTransactionSubType::EARLY_ADVANCE_INSTALLMENT->value,
                ])
                ->pluck('reference_id')
                ->filter()
                ->unique()
                ->toArray();

            if (empty($installmentIds)) {
                return;
            }

            // Get all installments that were marked as paid by this payroll
            $paidInstallments = EmployeeAdvanceInstallment::query()
                ->whereIn('id', $installmentIds)
                ->where('is_paid', true)
                ->get();

            if ($paidInstallments->isEmpty()) {
                return;
            }

            // Group installments by advance_request_id to update AdvanceRequest later
            $advanceRequestUpdates = [];

            foreach ($paidInstallments as $installment) {
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

                // Revert installment to unpaid
                $installment->update([
                    'is_paid' => false,
                    'paid_at' => null,
                    'paid_by' => null,
                    'payroll_id' => null,
                    'payment_method' => null,
                ]);
            }

            // Update AdvanceRequest: decrement paid_installments and increment remaining_total
            foreach ($advanceRequestUpdates as $advanceRequestId => $data) {
                $advanceRequest = AdvanceRequest::find($advanceRequestId);
                if ($advanceRequest) {
                    $advanceRequest->decrement('paid_installments', $data['count']);
                    $advanceRequest->increment('remaining_total', $data['amount']);

                    // Ensure paid_installments doesn't go below 0
                    if ($advanceRequest->paid_installments < 0) {
                        $advanceRequest->update(['paid_installments' => 0]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silent fail - log for debugging if needed
            // \Log::error('Failed to revert installments: ' . $e->getMessage());
        }
    }
 

    /**
     * Settle active carry forwards using a recovery transaction.
     */
    protected function settleCarryForwards(\App\Models\SalaryTransaction $txn): void
    {
        $amountToSettle = $txn->amount;
        $activeDebts = \App\Models\CarryForward::query()
            ->where('employee_id', $txn->employee_id)
            ->where('status', 'active')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        foreach ($activeDebts as $debt) {
            if ($amountToSettle <= 0) break;

            $canSettle = min($amountToSettle, $debt->remaining_balance);

            $debt->settled_amount += $canSettle;
            $debt->remaining_balance -= $canSettle;
            $amountToSettle -= $canSettle;

            if ($debt->remaining_balance <= 0) {
                $debt->status = 'settled';
            }
            $debt->save();
        }
    }
}
