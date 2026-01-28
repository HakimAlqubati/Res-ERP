<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\Allowance;
use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * حساب البدلات (العامة والخاصة)
 */
class AllowanceCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب جميع البدلات للموظف
     */
    public function calculate(CalculationContext $context): array
    {
        $allowanceItems = [];
        $allowanceTotal = 0.0;

        // 1) البدلات العامة
        $generalAllowances = Allowance::query()
            ->where('is_specific', 0)
            ->where('active', 1)
            ->get(['id', 'name', 'is_percentage', 'amount', 'percentage']);

        foreach ($generalAllowances as $a) {
            $amount = $a->is_percentage
                ? ($context->salary * ($a->percentage / 100))
                : (float)$a->amount;

            if ($amount <= 0) {
                continue;
            }

            $allowanceItems[] = [
                'id' => $a->id,
                'name' => $a->name,
                'amount' => $this->round($amount),
                'is_percentage' => $a->is_percentage,
                'value' => $a->is_percentage ? $a->percentage : $a->amount,
                'type' => 'general',
            ];

            $allowanceTotal += $amount;
        }

        // 2) البدلات الخاصة بالموظف
        $specificAllowances = $context->employee->allowances()
            ->with('allowance:id,name,is_percentage,amount,percentage')
            ->get();

        foreach ($specificAllowances as $empAllowance) {
            $a = $empAllowance->allowance;
            if (!$a) {
                continue;
            }

            // إذا الموظف عنده نسبة أو مبلغ خاص -> استخدمه
            $isPercentage = $empAllowance->is_percentage ?? $a->is_percentage;
            $percentage   = $empAllowance->percentage   ?? $a->percentage;
            $fixedAmount  = $empAllowance->amount       ?? $a->amount;

            $amount = $isPercentage
                ? ($context->salary * ($percentage / 100))
                : (float) $fixedAmount;

            if ($amount <= 0) {
                continue;
            }

            $allowanceItems[] = [
                'id'            => $a->id,
                'name'          => $a->name,
                'amount'        => $this->round($amount),
                'is_percentage' => (bool) $isPercentage,
                'value'         => $isPercentage ? $percentage : $fixedAmount,
                'type'          => 'specific',
            ];

            $allowanceTotal += $amount;
        }

        return [
            'items' => $allowanceItems,
            'total' => $this->round($allowanceTotal),
        ];
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
