<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\Deduction;
use App\Modules\HR\Payroll\DTOs\CalculationContext;
use Carbon\Carbon;

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

        // نسبة أيام هذا الـ segment من إجمالي أيام الشهر (للتوزيع النسبي عند multi-segment)
        $ratio = $this->calculateSegmentRatio($context);

        // الاستقطاعات الخاصة بالموظف
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

            $fullAmount = $isPercentage
                ? ($context->salary * ($percentage / 100))
                : (float) $fixedAmount;

            if ($fullAmount <= 0) {
                continue;
            }

            // توزيع مبلغ الاستقطاع نسبياً حسب أيام الفترة (segment)
            $amount = $this->round($fullAmount * $ratio);

            if ($amount <= 0) {
                continue;
            }

            $deductionItems[] = [
                'id'            => $d->id,
                'name'          => $d->name,
                'amount'        => $amount,
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

    /**
     * حساب نسبة أيام الفترة الحالية (segment) من إجمالي أيام الشهر.
     * إذا كانت الفترة تغطي الشهر كاملاً، ترجع 1.0.
     */
    protected function calculateSegmentRatio(CalculationContext $context): float
    {
        if (!$context->periodYear || !$context->periodMonth) {
            return 1.0;
        }

        $monthStart = sprintf('%04d-%02d-01', $context->periodYear, $context->periodMonth);
        $monthEnd   = date('Y-m-t', strtotime($monthStart));
        $totalDays  = (int) date('t', strtotime($monthStart));

        $segStart = $context->periodStartDate ?? $monthStart;
        $segEnd   = $context->periodEndDate   ?? $monthEnd;

        // إذا كانت الفترة تغطي الشهر كاملاً — لا حاجة للتوزيع
        if ($segStart <= $monthStart && $segEnd >= $monthEnd) {
            return 1.0;
        }

        $segDays = (int) Carbon::parse($segStart)->diffInDays(Carbon::parse($segEnd)) + 1;

        return min(1.0, max(0.0, $segDays / $totalDays));
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
