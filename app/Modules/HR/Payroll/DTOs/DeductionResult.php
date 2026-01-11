<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\DTOs;

/**
 * نتيجة حساب الخصومات المتعلقة بالحضور
 */
final class DeductionResult
{
    public function __construct(
        public float $absenceDeduction,
        public float $lateDeduction,
        public float $missingHoursDeduction,
        public float $earlyDepartureDeduction,
        public int $absentDays,
        public float $lateHours,
        public float $missingHours,
        public float $earlyDepartureHours,
    ) {}

    public function total(): float
    {
        return $this->absenceDeduction
            + $this->lateDeduction
            + $this->missingHoursDeduction
            + $this->earlyDepartureDeduction;
    }
}
