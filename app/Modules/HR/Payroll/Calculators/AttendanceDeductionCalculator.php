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
        if ($monthDays > self::STANDARD_MONTH_DAYS) {
            // الخصم الأساسي هو عدد أيام الغياب
            $effectiveAbsentDays = $absentDays;

            // حالة خاصة: إذا حضر يوم واحد فقط (غاب 30 يوم في شهر 31)
            // يجب أن يخصم له 29 يوم فقط، لكي يتبقى له راتب يوم واحد
            if ($absentDays == self::STANDARD_MONTH_DAYS) {
                $effectiveAbsentDays = self::STANDARD_MONTH_DAYS - 1;
            }

            // تسقيف الخصم عند 30 يوم (في حال غاب 31 يوم)
            $effectiveAbsentDays = min($effectiveAbsentDays, self::STANDARD_MONTH_DAYS);
        }

        // 2. شهر < 30 يوم (فبراير):
        // إذا غاب الشهر كاملاً، يخصم 30 يوم (ليكون الراتب صفر)
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
