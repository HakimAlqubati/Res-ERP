<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\DTOs;

use App\Models\Employee;

/**
 * السياق المشترك الذي يُمرر لجميع الـ Calculators
 */
final class CalculationContext
{
    public function __construct(
        public Employee $employee,
        public array $employeeData,
        public float $salary,
        public int $workingDays,
        public int $dailyHours,
        public int $monthDays,
        public ?int $periodYear,
        public ?int $periodMonth,
        public ?string $periodEndDate = null,
        public ?string $periodStartDate = null,   // ← بداية فترة الفرع (segment)
        public ?RateResult $rates = null,
    ) {}

    /**
     * إنشاء نسخة جديدة مع معدلات محسوبة
     */
    public function withRates(RateResult $rates): self
    {
        return new self(
            employee:        $this->employee,
            employeeData:    $this->employeeData,
            salary:          $this->salary,
            workingDays:     $this->workingDays,
            dailyHours:      $this->dailyHours,
            monthDays:       $this->monthDays,
            periodYear:      $this->periodYear,
            periodMonth:     $this->periodMonth,
            periodEndDate:   $this->periodEndDate,
            periodStartDate: $this->periodStartDate,   // ← حفظ قيمة الـ segment
            rates:           $rates,
        );
    }

    /**
     * بداية فترة العمل — تُعطي الأولوية لبداية فترة الفرع (segment) إن وُجدت.
     */
    public function periodStart(): string
    {
        return $this->periodStartDate
            ?? sprintf('%04d-%02d-01', $this->periodYear, $this->periodMonth);
    }

    /**
     * الحصول على نهاية الفترة
     */
    public function periodEnd(): string
    {
        return $this->periodEndDate ?? date('Y-m-t', strtotime($this->periodStart()));
    }
}
