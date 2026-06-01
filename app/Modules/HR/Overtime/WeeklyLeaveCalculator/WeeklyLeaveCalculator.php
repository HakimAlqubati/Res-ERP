<?php

namespace App\Modules\HR\Overtime\WeeklyLeaveCalculator;

use App\Models\Setting;

class WeeklyLeaveCalculator
{
    /**
     * الحد القياسي للإجازات في الشهر (للتوافق الرجعي إذا لم تكن الإعدادات متوفرة)
     */
    public const DEFAULT_MONTHLY_LEAVE = 4;

    /**
     * جلب إجمالي إجازات الشهر مباشرة من الإعدادات
     * (الحقل ما زال اسمه القديم لكن قيمته الآن هي الإجمالي الشهري: 4, 8, الخ)
     */
    public static function getStandardMonthlyLeave(): int
    {
        if (class_exists(Setting::class)) {
            return (int) Setting::getSetting('weekly_leave_days_earned', self::DEFAULT_MONTHLY_LEAVE);
        }

        return self::DEFAULT_MONTHLY_LEAVE;
    }

    /**
     * الاحتساب الرقمي للإجازات والميزان المالي.
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

            // جلب المتغير الشهري (مثلاً 4 أو 8)
            $standardMonthlyLeave = self::getStandardMonthlyLeave();

            // أيام العمل المفترضة في الشهر (لحماية النظام من القسمة على صفر)
            $expectedWorkDays = max(1, $totalMonthDays - $standardMonthlyLeave);

            // =================================================================
            // 2. رصيد الراحة المكتسب (نظام النسبة والتناسب الشهري)
            // =================================================================
            $earnedOffDays = $applyWeeklyLeave
                ? (int) round(($actualWorkedDays / $expectedWorkDays) * $standardMonthlyLeave)
                : 0;

            $cappedEarnedDays = $applyWeeklyLeave ? min($standardMonthlyLeave, $earnedOffDays) : 0;

            // =================================================================
            // 3. المعادلة الذهبية (الميزان الرقمي)
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

            // 5. تفصيل الخصم
            $leavePenaltyDisplay = 0;
            $absentPenaltyDisplay = 0;

            if ($totalPenaltyDays > 0) {
                if ($applyWeeklyLeave && $cappedEarnedDays < $standardMonthlyLeave) {
                    $leavePenaltyDisplay = $standardMonthlyLeave - $cappedEarnedDays;
                }
                $absentPenaltyDisplay = max(0, $totalPenaltyDays - $leavePenaltyDisplay);
            }

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
                    'work_remainder' => 0, // لم يعد له حاجة في نظام النسبة
                ],
                'result' => [
                    'leave_penalty' => $leavePenaltyDisplay,
                    'final_absent_penalty' => $absentPenaltyDisplay,
                    'total_deduction_days' => $totalPenaltyDays,
                    'overtime_days' => $overtimeDays,
                    'payable_days' => $actualWorkedDays + $cappedEarnedDays,
                ],
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
