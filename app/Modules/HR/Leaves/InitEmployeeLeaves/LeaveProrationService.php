<?php

namespace App\Modules\HR\Leaves\InitEmployeeLeaves;

use App\Models\LeaveType;
use Carbon\Carbon;

/**
 * Class LeaveProrationService
 * خدمة احتساب الإجازات بالتناسب (Proration)
 *
 * Responsible for calculating the exact entitled leave days for an employee,
 * applying proration logic based on their joining date.
 * مسؤولة عن حساب أيام الإجازة المستحقة بدقة للموظف،
 * مع تطبيق منطق التناسب بناءً على تاريخ التحاقه بالعمل.
 *
 * @package App\Modules\HR\Leaves\InitEmployeeLeaves
 */
class LeaveProrationService
{
    /**
     * Calculate the entitled days for a specific period.
     * حساب أيام الاستحقاق لفترة زمنية محددة.
     *
     * @param LeaveType $leaveType   نوع الإجازة
     * @param string    $joinDateString تاريخ التحاق الموظف بالعمل
     * @param int       $targetYear  السنة المستهدفة للحساب
     * @param int|null  $targetMonth الشهر المستهدف (اختياري، للإجازات الشهرية)
     * @return float                 عدد أيام الاستحقاق (مقرّب لأقرب نصف يوم)
     */
    public static function calculateEntitlement(
        LeaveType $leaveType,
        string $joinDateString,
        int $targetYear,
        ?int $targetMonth = null
    ): float {
        // عدد أيام الإجازة الكاملة المحددة في نوع الإجازة
        $countDays = (float) $leaveType->count_days;

        // إذا لم يكن نوع الإجازة يستلزم تناسباً عند التوظيف → إرجاع الاستحقاق الكامل مباشرةً
        // If proration on hire is not enabled → return full entitlement directly
        if (!$leaveType->prorate_on_hire) {
            return $countDays;
        }

        // تحويل تاريخ الالتحاق إلى كائن Carbon وضبطه على بداية اليوم
        // Parse the joining date and set it to the start of the day
        $joinDate = Carbon::parse($joinDateString)->startOfDay();

        // تحديد حدود فترة التقييم (بداية ونهاية الشهر أو السنة)
        // Determine the start and end boundaries of the evaluation period
        $period = self::resolvePeriodBoundaries($leaveType, $targetYear, $targetMonth);

        // الموظف التحق قبل بداية الفترة → استحقاق كامل
        // Employee joined before the period started → Full entitlement
        if ($joinDate->lte($period['start'])) {
            return $countDays;
        }

        // الموظف التحق بعد نهاية الفترة → لا استحقاق
        // Employee joined after the period ended → Zero entitlement
        if ($joinDate->gt($period['end'])) {
            return 0.0;
        }

        // الموظف التحق خلال الفترة → حساب الاستحقاق بالتناسب
        // Employee joined during the period → calculate prorated entitlement
        return self::computeProratedDays($joinDate, $period['start'], $period['end'], $countDays);
    }

    /**
     * Determine the start and end dates of the evaluation period.
     * تحديد تواريخ بداية ونهاية فترة التقييم.
     *
     * @param LeaveType $leaveType نوع الإجازة (يحدد إن كانت الفترة شهرية أو سنوية)
     * @param int       $year     السنة المستهدفة
     * @param int|null  $month    الشهر المستهدف (إن وُجد)
     * @return array{start: Carbon, end: Carbon} مصفوفة تحتوي على تاريخ البداية والنهاية
     */
    private static function resolvePeriodBoundaries(LeaveType $leaveType, int $year, ?int $month): array
    {
        // إذا كانت الإجازة شهرية ومُحدد الشهر → الفترة هي الشهر المحدد
        // If the balance period is monthly and a month is provided → use that specific month
        if ($leaveType->balance_period === LeaveType::BALANCE_PERIOD_MONTHLY && $month) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end   = $start->copy()->endOfMonth();
        } else {
            // وإلا → الفترة هي السنة كاملة
            // Otherwise → use the full year
            $start = Carbon::create($year, 1, 1)->startOfYear();
            $end   = $start->copy()->endOfYear();
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Execute the mathematical proration formula.
     * تطبيق معادلة التناسب الرياضية لحساب الأيام المستحقة.
     *
     * المعادلة: (أيام العمل الفعلي في الفترة / إجمالي أيام الفترة) × إجمالي أيام الإجازة
     * Formula:  (worked days in period / total days in period) × total leave days
     *
     * @param Carbon $joinDate       تاريخ التحاق الموظف
     * @param Carbon $periodStart    تاريخ بداية الفترة
     * @param Carbon $periodEnd      تاريخ نهاية الفترة
     * @param float  $totalCountDays إجمالي أيام الإجازة الكاملة
     * @return float                 الأيام المستحقة بالتناسب (مقرّبة لأقرب نصف يوم)
     */
    private static function computeProratedDays(Carbon $joinDate, Carbon $periodStart, Carbon $periodEnd, float $totalCountDays): float
    {
        // إجمالي عدد أيام الفترة (بداية ونهاية شاملتان)
        // Total number of days in the period (inclusive of start and end)
        $totalDaysInPeriod = $periodStart->diffInDays($periodEnd) + 1;

        // عدد الأيام التي عمل فيها الموظف فعلياً خلال الفترة
        // Number of days the employee actually worked within the period
        $workedDaysInPeriod = $joinDate->diffInDays($periodEnd) + 1;

        // تطبيق معادلة التناسب
        // Apply the proration formula
        $proratedValue = ($workedDaysInPeriod / $totalDaysInPeriod) * $totalCountDays;

        // تقريب النتيجة لأقرب نصف يوم (دقة نصف يوم)
        // Round to the nearest 0.5 (Half-day precision)
        return round($proratedValue * 2) / 2;
    }
}
