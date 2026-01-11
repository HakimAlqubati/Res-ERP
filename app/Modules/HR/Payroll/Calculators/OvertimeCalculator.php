<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * حساب الأوفرتايم (العمل الإضافي)
 */
class OvertimeCalculator
{
    public const DEFAULT_OVERTIME_MULTIPLIER = 1.5;
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected float $overtimeMultiplier = self::DEFAULT_OVERTIME_MULTIPLIER,
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب مبلغ الأوفرتايم
     */
    public function calculate(CalculationContext $context, float $approvedOvertimeHours): array
    {
        $rates = $context->rates;
        if (!$rates) {
            throw new \InvalidArgumentException('Rates must be calculated first');
        }

        $overtimeAmount = $this->round(
            $approvedOvertimeHours * $rates->hourlyRate * $this->overtimeMultiplier
        );

        return [
            'hours' => $approvedOvertimeHours,
            'amount' => $overtimeAmount,
            'multiplier' => $this->overtimeMultiplier,
            'hourly_rate' => $rates->hourlyRate,
        ];
    }

    /**
     * تعيين مضاعف الأوفرتايم
     */
    public function setMultiplier(float $multiplier): self
    {
        $this->overtimeMultiplier = $multiplier;
        return $this;
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
