<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\AdvanceWage;
use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * حساب الأجور المقدمة المستحق خصمها من راتب الموظف
 *
 * يجلب جميع الأجور المقدمة بحالة (pending) للموظف في الفترة المحددة،
 * وذلك ليتم بناء حركات الخصم الخاصة بها وقت إنشاء الـ Payroll.
 */
class AdvanceWageCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب إجمالي الأجور المقدمة المعلقة للموظف في الشهر المحدد
     *
     * @return array{items: array, total: float}
     */
    public function calculate(CalculationContext $context): array
    {
        if (!$context->periodYear || !$context->periodMonth) {
            return ['items' => [], 'total' => 0.0];
        }

        $rows = AdvanceWage::query()
            ->forEmployee($context->employee->id)
            ->forPeriod($context->periodYear, $context->periodMonth)
            ->pending()
            ->get(['id', 'amount', 'reason', 'notes', 'approved_at']);

        if ($rows->isEmpty()) {
            return ['items' => [], 'total' => 0.0];
        }

        $items = [];
        $total = 0.0;

        foreach ($rows as $row) {
            $amount = (float) $row->amount;
            if ($amount <= 0) continue;

            $items[] = [
                'advance_wage_id' => $row->id,
                'amount'          => $this->round($amount),
                'reason'          => $row->reason,
                'notes'           => $row->notes,
            ];

            $total += $amount;
        }

        return [
            'items' => $items,
            'total' => $this->round($total),
        ];
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
