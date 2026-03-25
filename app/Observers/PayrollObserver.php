<?php

namespace App\Observers;

use App\Models\Payroll;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\AdvanceRequest;
use App\Models\SalaryTransaction;
use App\Enums\HR\Payroll\SalaryTransactionSubType;
use Illuminate\Support\Facades\Log;

class PayrollObserver
{
    /**
     * Handle the Payroll "deleted" event.
     */
    public function deleted(Payroll $payroll): void
    {
        try {
            // 1. التراجع عن أقساط السلف المرتبطة بكشف الراتب هذا
            $this->revertInstallmentsToUnpaid($payroll);

            // 2. حذف حركات الراتب (Transactions) المرتبطة بهذا الكشف
            $payroll->transactions()->delete();
        } catch (\Exception $e) {
            Log::error('Payroll deletion error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Payroll "forceDeleted" event.
     */
    public function forceDeleted(Payroll $payroll): void
    {
        try {
            // حذف حركات الراتب نهائياً
            $payroll->transactions()->withTrashed()->forceDelete();
        } catch (\Exception $e) {
            Log::error('Payroll force deletion error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Payroll "restored" event.
     */
    public function restored(Payroll $payroll): void
    {
        try {
            // استعادة حركات الراتب
            $payroll->transactions()->onlyTrashed()->restore();
        } catch (\Exception $e) {
            Log::error('Payroll restore error: ' . $e->getMessage());
        }
    }

    /**
     * دالة للتراجع عن الأقساط
     */
    protected function revertInstallmentsToUnpaid(Payroll $payroll): void
    {
        try {
            // استخراج معرّفات الأقساط التي تم دفعها عبر كشف الراتب هذا
            $installmentIds = SalaryTransaction::query()
                ->where('payroll_id', $payroll->id)
                ->withTrashed()
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

            $paidInstallments = EmployeeAdvanceInstallment::query()
                ->whereIn('id', $installmentIds)
                ->where('is_paid', true)
                ->get();

            if ($paidInstallments->isEmpty()) {
                return;
            }

            $advanceRequestUpdates = [];

            foreach ($paidInstallments as $installment) {
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

                // إرجاع حالة القسط لغير مدفوع
                $installment->update([
                    'is_paid' => false,
                    'paid_date' => null,
                    'paid_by' => null,
                    'status' => EmployeeAdvanceInstallment::STATUS_SCHEDULED,
                    'payroll_id' => null,
                    'payment_method' => null,
                ]);
            }

            // تحديث إجمالي السلفة المدفوع والمتبقي
            foreach ($advanceRequestUpdates as $advanceRequestId => $data) {
                $advanceRequest = AdvanceRequest::find($advanceRequestId);
                if ($advanceRequest) {
                    $advanceRequest->decrement('paid_installments', $data['count']);
                    $advanceRequest->increment('remaining_total', $data['amount']);

                    // تحكم في حالة القيم السلبية للسلامة
                    if ($advanceRequest->paid_installments < 0) {
                        $advanceRequest->update(['paid_installments' => 0]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Payroll revert installments error: ' . $e->getMessage());
        }
    }
}
