<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Modules\HR\Payroll\DTOs\CalculationContext;
use App\Modules\HR\Payroll\DTOs\DeductionResult;

/**
 * حساب خصومات الحضور (الغياب، التأخير، الساعات الناقصة، المغادرة المبكرة)
 */
class AttendanceDeductionCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;
    public const STANDARD_MONTH_DAYS = 30;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب جميع خصومات الحضور
     */
    public function calculate(CalculationContext $context): DeductionResult
    {
        $rates = $context->rates;
        if (!$rates) {
            throw new \InvalidArgumentException('Rates must be calculated first');
        }

        $employeeData = $context->employeeData;

        // استخراج الإحصائيات
        $stats = $employeeData['statistics'] ?? [];
        // dd($stats);
        // $absentDays = (int)($stats['absent'] ?? $stats['absent_days'] ?? 0);
        $absentDays = $stats['weekly_leave_calculation']['result']['total_deduction_days'] ?? 0;
        $presentDays = (int)($stats['present_days'] ?? $stats['present'] ?? 0);
        // dd($absentDays);

        $monthDays = $context->monthDays;
        $effectiveAbsentDays = $absentDays;

        // 1. شهر > 30 يوم (مثال: 31):
        // القاعدة: الخصم = 30 - الأيام المدفوعة
        // الأيام المدفوعة = (أيام الشهر - الغياب الفعلي)
        // مثال: غياب 30 (حضور 1) => الأيام المدفوعة = 31 - 30 = 1. الخصم = 30 - 1 = 29.
        if ($monthDays > self::STANDARD_MONTH_DAYS) {
            $paidDays = $monthDays - $absentDays;
            $effectiveAbsentDays = max(0, self::STANDARD_MONTH_DAYS - $paidDays);
        }

        // 2. شهر < 30 يوم (فبراير):
        // القاعدة: إذا الغياب كامل الشهر، الخصم = 30. (لكي لا يتبقى له راتب)
        // إذا الغياب جزئي، نخصم الأيام الفعلية فقط (ميزة للموظف في الشهر القصير)
        if ($monthDays < self::STANDARD_MONTH_DAYS && $absentDays >= $monthDays) {
            $effectiveAbsentDays = self::STANDARD_MONTH_DAYS;
        }

        // استخراج ساعات التأخير
        $lateHours = $this->extractLateHours($employeeData);

        // استخراج الساعات الناقصة
        $missingHours = $this->extractMissingHours($employeeData);

        // استخراج ساعات المغادرة المبكرة
        $earlyDepartureHours = $this->extractEarlyDepartureHours($employeeData);

        // حساب الخصومات
        $absenceDeduction = $this->round($effectiveAbsentDays * $rates->dailyRate);
        $lateDeduction = $this->round($lateHours * $rates->hourlyRate);
        $missingHoursDeduction = $this->round($missingHours * $rates->hourlyRate);
        $earlyDepartureDeduction = $this->round($earlyDepartureHours * $rates->hourlyRate);

        // تطبيق الاستثناءات من ملف الموظف
        if ($context->employee->discount_exception_if_absent) {
            $absenceDeduction = 0.0;
        }

        if ($context->employee->discount_exception_if_attendance_late) {
            $lateDeduction = 0.0;
            $earlyDepartureDeduction = 0.0; // إعفاء الانصراف المبكر للموظف المعفي من التأخيرات
        }

        return new DeductionResult(
            absenceDeduction: $absenceDeduction,
            lateDeduction: $lateDeduction,
            missingHoursDeduction: $missingHoursDeduction,
            earlyDepartureDeduction: $earlyDepartureDeduction,
            absentDays: $absentDays,
            lateHours: $lateHours,
            missingHours: $missingHours,
            earlyDepartureHours: $earlyDepartureHours,
        );
    }

    /**
     * استخراج ساعات التأخير من بيانات الموظف
     */
    protected function extractLateHours(array $data): float
    {
        $late = $data['late_hours'] ?? null;
        if (is_array($late)) {
            if (isset($late['totalHoursFloat'])) {
                return (float)$late['totalHoursFloat'];
            }
            if (isset($late['hours'], $late['minutes'])) {
                return (int)$late['hours'] + ((int)$late['minutes'] / 60);
            }
        }
        return 0.0;
    }

    /**
     * استخراج الساعات الناقصة
     */
    protected function extractMissingHours(array $data): float
    {
        $mh = $data['total_missing_hours'] ?? null;
        if (is_array($mh) && isset($mh['total_hours'])) {
            return (float) $mh['total_hours'];
        }
        return 0.0;
    }

    /**
     * استخراج ساعات المغادرة المبكرة
     */
    protected function extractEarlyDepartureHours(array $data): float
    {
        $ed = $data['total_early_departure_minutes'] ?? null;
        if (is_array($ed) && isset($ed['total_hours'])) {
            return (float) $ed['total_hours'];
        }
        return 0.0;
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
