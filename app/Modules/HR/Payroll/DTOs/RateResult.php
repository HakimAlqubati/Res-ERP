<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\DTOs;

/**
 * نتيجة حساب الأجرة اليومية والساعية
 */
final class RateResult
{
    public function __construct(
        public float $dailyRate,
        public float $hourlyRate,
        public string $method,
    ) {}
}
