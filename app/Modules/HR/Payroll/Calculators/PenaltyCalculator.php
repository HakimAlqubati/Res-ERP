<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\PenaltyDeduction;
use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * حساب خصومات الجزاءات المعتمدة
 */
class PenaltyCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب جزاءات الموظف المعتمدة للشهر
     */
    public function calculate(CalculationContext $context): array
    {
        $penaltyItems = [];
        $penaltyTotal = 0.0;

        if (!$context->periodYear || !$context->periodMonth) {
            return [
                'items' => $penaltyItems,
                'total' => $penaltyTotal,
            ];
        }

        // جلب الجزاءات المعتمدة لهذا الموظف في الشهر/السنة المحددة
        $penalties = PenaltyDeduction::query()
            ->where('employee_id', $context->employee->id)
            ->where('status', PenaltyDeduction::STATUS_APPROVED)
            ->where('year', $context->periodYear)
            ->where('month', $context->periodMonth)
            ->with(['deduction:id,name'])
            ->get();

        foreach ($penalties as $p) {
            $amount = (float)$p->penalty_amount;
            if ($amount <= 0) {
                continue;
            }

            $penaltyItems[] = [
                'id'             => $p->id,
                'deduction_id'   => $p->deduction_id,
                'deduction_name' => optional($p->deduction)->name ?? 'Penalty deduction',
                'amount'         => $this->round($amount),
                'description'    => $p->description,
                'date'           => $p->date,
                'reference_type' => PenaltyDeduction::class,
                'reference_id'   => $p->id,
            ];

            $penaltyTotal += $amount;
        }

        return [
            'items' => $penaltyItems,
            'total' => $this->round($penaltyTotal),
        ];
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
