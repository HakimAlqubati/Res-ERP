<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\Deduction;
use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * حساب الخصومات الخاصة بالموظف
 */
class CustomDeductionCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب الخصومات المخصصة للموظف
     */
    public function calculate(CalculationContext $context): array
    {
        $deductionItems = [];
        $deductionTotal = 0.0;

        // البدلات الخاصة بالموظف
        $specificDeductions = $context->employee->deductions()
            ->with('deduction:id,name,is_percentage,amount,percentage,applied_by')
            ->get();

        foreach ($specificDeductions as $empDeduction) {
            $d = $empDeduction->deduction;
            if (!$d) {
                continue;
            }

            // استخدام إعدادات الموظف الخاصة إذا وجدت، وإلا استخدام إعدادات الخصم الافتراضية
            $isPercentage = $empDeduction->is_percentage ?? $d->is_percentage;
            $percentage   = $empDeduction->percentage   ?? $d->percentage;
            $fixedAmount  = $empDeduction->amount       ?? $d->amount;

            $amount = $isPercentage
                ? ($context->salary * ($percentage / 100))
                : (float) $fixedAmount;

            if ($amount <= 0) {
                continue;
            }

            $deductionItems[] = [
                'id'            => $d->id,
                'name'          => $d->name,
                'amount'        => $this->round($amount),
                'is_percentage' => (bool) $isPercentage,
                'value'         => $isPercentage ? $percentage : $fixedAmount,
                'type'          => 'specific',
                'applied_by'    => $d->applied_by,
            ];

            if ($d->applied_by !== Deduction::APPLIED_BY_EMPLOYER) {
                $deductionTotal += $amount;
            }
        }

        return [
            'items' => $deductionItems,
            'total' => $this->round($deductionTotal),
        ];
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
