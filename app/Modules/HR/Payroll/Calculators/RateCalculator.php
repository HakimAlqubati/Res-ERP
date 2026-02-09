<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Enums\HR\Payroll\DailyRateMethod;
use App\Modules\HR\Payroll\DTOs\RateResult;

/**
 * حساب الأجرة اليومية والساعية
 */
class RateCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب المعدلات (الأجرة اليومية والساعية)
     */
    public function calculate(
        float $salary,
        int $workingDays,
        int $dailyHours,
        int $monthDays,
        string $dailyRateMethod
    ): RateResult {
        $dailyRate = match ($dailyRateMethod) {
            DailyRateMethod::By30Days->value              => $salary / 30,
            DailyRateMethod::ByMonthDays->value           => $salary / $monthDays,
            DailyRateMethod::ByWorkingDays->value         => $salary / $workingDays, // Calculated: monthDays - 4
            DailyRateMethod::ByEmployeeWorkingDays->value => $salary / $workingDays, // Employee's working_days field
            default                                       => $salary / $workingDays,
        };

        $dailyRate = $this->round($dailyRate);
        $hourlyRate = $dailyRate / $dailyHours;

        return new RateResult(
            dailyRate: $dailyRate,
            hourlyRate: $hourlyRate,
            method: $dailyRateMethod,
        );
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
