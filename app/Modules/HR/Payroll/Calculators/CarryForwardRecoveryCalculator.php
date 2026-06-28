<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\CarryForward;
use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * استرداد ديون الأشهر السابقة (Carry Forward Recovery)
 *
 * يجلب سجلات الديون النشطة من جدول hr_carry_forward ويُعيدها كخصم
 * ليتم إدراجها ضمن مجموع الخصومات في SalaryCalculatorService.
 *
 * ملاحظة: هذا الـ Calculator يُحسب فقط — التسوية الفعلية تحدث عند حفظ
 * الـ SalaryTransaction عبر Observer في SalaryTransaction::booted().
 */
class CarryForwardRecoveryCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب مبالغ الاسترداد من ديون الأشهر السابقة
     */
    public function calculate(CalculationContext $context): array
    {
        $recoveryItems = [];
        $recoveryTotal = 0.0;

        if (!$context->periodYear || !$context->periodMonth) {
            return [
                'items' => $recoveryItems,
                'total' => $recoveryTotal,
            ];
        }

        // جلب الديون النشطة لهذا الموظف — مرتبة من الأقدم (FIFO)
        // استبعاد ديون نفس الشهر الحالي حتى لا يُخصم الدين من الشهر الذي أنتجه
        $activeDebts = CarryForward::query()
            ->where('employee_id', $context->employee->id)
            ->where('status', 'active')
            ->where('remaining_balance', '>', 0)
            ->where(function ($q) use ($context) {
                $q->where('year', '<', $context->periodYear)
                  ->orWhere(function ($q2) use ($context) {
                      $q2->where('year', $context->periodYear)
                         ->where('month', '<', $context->periodMonth);
                  });
            })
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        if ($activeDebts->isEmpty()) {
            return [
                'items' => $recoveryItems,
                'total' => $recoveryTotal,
            ];
        }

        foreach ($activeDebts as $debt) {
            $amount = (float) $debt->remaining_balance;
            if ($amount <= 0) {
                continue;
            }

            $recoveryItems[] = [
                'carry_forward_id'    => (int) $debt->id,
                'from_year'           => (int) $debt->year,
                'from_month'          => (int) $debt->month,
                'original_amount'     => $this->round((float) $debt->total_amount),
                'remaining_before'    => $this->round($amount),
                'recovery_amount'     => $this->round($amount),
                'from_payroll_run_id' => $debt->from_payroll_run_id,
            ];

            $recoveryTotal += $amount;
        }

        return [
            'items' => $recoveryItems,
            'total' => $this->round($recoveryTotal),
        ];
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
