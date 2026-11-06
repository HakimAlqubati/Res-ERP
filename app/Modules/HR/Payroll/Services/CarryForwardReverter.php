<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Services;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\CarryForward;
use App\Models\SalaryTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * عكس أثر الـ Carry Forward عند حذف كشف راتب / Payroll Run.
 *
 * - حركات الاسترداد (reference = CarryForward): تُعاد التسوية (settled_amount / remaining_balance / status).
 * - حركات العجز الجديد (بدون reference): يُحذف سجل الدين الناتج إن لم يُسدَّد منه شيء.
 *
 * يعمل على الحركات "الحيّة" فقط (غير المحذوفة) لمنع العكس المزدوج،
 * لذلك يجب استدعاؤه قبل حذف حركات الراتب.
 */
class CarryForwardReverter
{
    public function revertForPayroll(int $payrollId): void
    {
        $this->revert(fn(Builder $q) => $q->where('payroll_id', $payrollId));
    }

    public function revertForPayrollRun(int $payrollRunId): void
    {
        $this->revert(fn(Builder $q) => $q->where('payroll_run_id', $payrollRunId));
    }

    protected function revert(\Closure $scope): void
    {
        try {
            DB::transaction(function () use ($scope) {
                $query = SalaryTransaction::query()
                    ->where('type', SalaryTransactionType::TYPE_CARRY_FORWARD->value)
                    ->where('operation', SalaryTransaction::OPERATION_SUB);

                $scope($query);

                foreach ($query->get() as $txn) {
                    if ($txn->reference_type === CarryForward::class && $txn->reference_id) {
                        $this->revertRecovery((int) $txn->reference_id, (float) $txn->amount);
                    } else {
                        $this->removeGeneratedDebt($txn);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('CarryForward revert error: ' . $e->getMessage());
        }
    }

    /**
     * التراجع عن تسوية دين سابق تمت عبر حركة استرداد.
     */
    protected function revertRecovery(int $carryForwardId, float $amount): void
    {
        $cf = CarryForward::find($carryForwardId);
        if (!$cf) {
            return;
        }

        $restore = min($amount, (float) $cf->settled_amount);
        if ($restore <= 0) {
            return;
        }

        $cf->settled_amount    = round((float) $cf->settled_amount - $restore, 2);
        $cf->remaining_balance = round(min((float) $cf->remaining_balance + $restore, (float) $cf->total_amount), 2);

        if ($cf->remaining_balance > 0) {
            $cf->status = 'active';
        }

        $cf->save();
    }

    /**
     * حذف الدين الناتج عن هذا الراتب (العجز) إن لم يُسدَّد منه شيء.
     */
    protected function removeGeneratedDebt(SalaryTransaction $txn): void
    {
        $payrollRunId = $txn->payroll_run_id ?? $txn->payroll?->payroll_run_id;

        if (!$payrollRunId) {
            return;
        }

        $cf = CarryForward::query()
            ->where('employee_id', $txn->employee_id)
            ->where('from_payroll_run_id', $payrollRunId)
            ->first();

        if (!$cf) {
            return;
        }

        if ((float) $cf->settled_amount > 0) {
            Log::warning("CarryForward ID={$cf->id} has settlements; not removed on payroll deletion.");
            return;
        }

        $cf->delete();
    }
}
