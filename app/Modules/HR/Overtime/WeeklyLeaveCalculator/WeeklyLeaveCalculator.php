<?php

namespace App\Modules\HR\Overtime\WeeklyLeaveCalculator;

class WeeklyLeaveCalculator
{
    /**
     * عدد أيام العمل المطلوبة لاكتساب الإجازة (للتوافق الرجعي)
     */
    public const WORK_DAYS_PER_LEAVE = 5;

    /**
     * عدد أيام الراحة المكتسبة (للتوافق الرجعي)
     */
    public const LEAVE_DAYS_EARNED = 1;

    /**
     * الحد القياسي للإجازات (4 أسابيع × عدد أيام الراحة) - للتوافق الرجعي
     */
    private const STANDARD_MONTHLY_LEAVE = 4;

    /**
     * جلب عدد أيام الراحة المكتسبة ديناميكياً من الإعدادات
     */
    public static function getLeaveDaysEarned(): int
    {
        if (class_exists(\App\Models\Setting::class)) {
            return (int) \App\Models\Setting::getSetting('weekly_leave_days_earned', self::LEAVE_DAYS_EARNED);
        }
        return self::LEAVE_DAYS_EARNED;
    }

    /**
     * جلب عدد أيام العمل المطلوبة لاكتساب الإجازة ديناميكياً
     */
    public static function getWorkDaysPerLeave(): int
    {
        return 7 - self::getLeaveDaysEarned();
    }

    /**
     * جلب الحد القياسي للإجازات شهرياً ديناميكياً
     */
    public static function getStandardMonthlyLeave(): int
    {
        return 4 * self::getLeaveDaysEarned();
    }

    /**
     * الاحتساب الرقمي للإجازات الأسبوعية والميزان المالي.
     *
     * @param  int  $totalMonthDays  إجمالي أيام الشهر (الوعاء الزمني)
     * @param  int  $absentDays  عدد أيام الغياب
     * @param  array  $context  سياق الاستدعاء:
     *                          - is_period_ended (bool): هل انتهت الفترة/الشهر؟
     *                          - is_for_payroll  (bool): هل الاحتساب لأغراض الرواتب؟
     *                          يُطبَّق احتساب الإجازات الأسبوعية فقط عند تحقق الشرطين معاً.
     */
    public function calculate(int $totalMonthDays, int $absentDays, array $context = []): array
    {
        try {
            $isPeriodEnded = (bool) ($context['is_period_ended'] ?? false);
            $isForPayroll = (bool) ($context['is_for_payroll'] ?? false);
            $hasAutoLeave = (bool) ($context['has_auto_weekly_leave'] ?? true);
            $applyWeeklyLeave = $isPeriodEnded && $isForPayroll && $hasAutoLeave;

            // حماية: الغياب لا يتجاوز إجمالي الأيام
            if ($absentDays > $totalMonthDays) {
                $absentDays = $totalMonthDays;
            }

            // 1. أيام العمل الصافية
            $actualWorkedDays = $totalMonthDays - $absentDays;

            // جلب المتغيرات ديناميكياً
            $leaveDaysEarned     = self::getLeaveDaysEarned();
            $workDaysPerLeave    = self::getWorkDaysPerLeave();
            $standardMonthlyLeave = self::getStandardMonthlyLeave();

            // 2. رصيد الراحة المكتسب (يُحسب فقط عند تطبيق الإجازات الأسبوعية)
            $earnedOffDays = $applyWeeklyLeave
                ? (int) floor($actualWorkedDays / $workDaysPerLeave) * $leaveDaysEarned
                : 0;
            $cappedEarnedDays = $applyWeeklyLeave ? min($standardMonthlyLeave, $earnedOffDays) : 0;
            $workRemainder = $actualWorkedDays % $workDaysPerLeave;

            // =================================================================
            // 3. المعادلة الذهبية (الميزان الرقمي)
            // الرصيد = (ما قدمه الموظف + ما استحقه من راحة) - (المطلوب منه في الشهر)
            // =================================================================
            $netBalance = ($actualWorkedDays + $cappedEarnedDays) - $totalMonthDays;

            // 4. ترجمة الميزان إلى (إضافي) أو (خصم)
            $overtimeDays = 0;
            $totalPenaltyDays = 0;

            if ($netBalance > 0) {
                $overtimeDays = $netBalance;
            } elseif ($netBalance < 0) {
                $totalPenaltyDays = abs($netBalance);
            }

            // 5. تفصيل الخصم (للتوضيح فقط)
            // يُطبَّق فقط عند تفعيل الإجازات الأسبوعية — وإلا فكل الخصم غياب صافٍ
            $leavePenaltyDisplay = 0;
            $absentPenaltyDisplay = 0;

            if ($totalPenaltyDays > 0) {
                if ($applyWeeklyLeave && $cappedEarnedDays < self::STANDARD_MONTHLY_LEAVE) {
                    $leavePenaltyDisplay = self::STANDARD_MONTHLY_LEAVE - $cappedEarnedDays;
                }
                $absentPenaltyDisplay = max(0, $totalPenaltyDays - $leavePenaltyDisplay);
            }

            $payableDays = $actualWorkedDays + $cappedEarnedDays;

            return [
                'context' => [
                    'is_period_ended' => $isPeriodEnded,
                    'is_for_payroll' => $isForPayroll,
                    'weekly_leave_applied' => $applyWeeklyLeave,
                ],
                'inputs' => [
                    'total_days' => $totalMonthDays,
                    'absent_days' => $absentDays,
                ],
                'analysis' => [
                    'worked_days' => $actualWorkedDays,
                    'earned_leave_days' => $cappedEarnedDays,
                    'work_remainder' => $workRemainder,
                ],
                'result' => [
                    'leave_penalty' => $leavePenaltyDisplay,
                    'final_absent_penalty' => $absentPenaltyDisplay,
                    'total_deduction_days' => $totalPenaltyDays,
                    'overtime_days' => $overtimeDays,
                    'payable_days' => $payableDays,
                ],
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
