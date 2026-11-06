<?php

namespace App\Modules\HR\Overtime\WeeklyLeaveCalculator;

class WeeklyLeaveCalculator
{
    /**
     * معدل الاستحقاق: يوم راحة واحد مقابل كل 6 أيام عمل
     */
    public const WORK_DAYS_PER_LEAVE = 6;

    /**
     * الحد القياسي للإجازات (المعيار الذي يقاس عليه الخصم أو الإضافي)
     */
    private const STANDARD_MONTHLY_LEAVE = 4;

    /**
     * الاحتساب الرقمي للإجازات الأسبوعية والميزان المالي.
     *
     * @param int   $totalMonthDays  إجمالي أيام الشهر (الوعاء الزمني)
     * @param int   $absentDays      عدد أيام الغياب
     * @param array $context         سياق الاستدعاء:
     *                               - is_period_ended (bool): هل انتهت الفترة/الشهر؟
     *                               - is_for_payroll  (bool): هل الاحتساب لأغراض الرواتب؟
     *                               يُطبَّق احتساب الإجازات الأسبوعية فقط عند تحقق الشرطين معاً.
     * @return array
     */
    public function calculate(int $totalMonthDays, int $absentDays, array $context = []): array
    {
        try {
            $isPeriodEnded    = (bool) ($context['is_period_ended'] ?? false);
            $isForPayroll     = (bool) ($context['is_for_payroll']  ?? false);
            $hasAutoLeave     = (bool) ($context['has_auto_weekly_leave'] ?? true);
            $applyWeeklyLeave = $isPeriodEnded && $isForPayroll && $hasAutoLeave;

            // الحد الأقصى للإجازة: يُستخدم الخاص بالموظف إن وُجد، وإلا الافتراضي
            $standardMonthlyLeave = isset($context['max_monthly_leave']) && $context['max_monthly_leave'] !== null
                ? (int) $context['max_monthly_leave']
                : self::STANDARD_MONTHLY_LEAVE;

            // حماية: الغياب لا يتجاوز إجمالي الأيام
            if ($absentDays > $totalMonthDays) {
                $absentDays = $totalMonthDays;
            }

            // 1. أيام العمل الصافية
            $actualWorkedDays = $totalMonthDays - $absentDays;

            // 2. رصيد الراحة المكتسب (يُحسب فقط عند تطبيق الإجازات الأسبوعية)
            $alreadyEarned    = (int) ($context['already_earned'] ?? 0);
            $prevRemainder    = (int) ($context['prev_remainder'] ?? 0);
            
            // نجمع أيام العمل الحالية مع باقي الأيام من السيجمنتات السابقة لضمان عدم ضياع الكسور
            $totalWorkedForLeave = $actualWorkedDays + $prevRemainder;
            $earnedOffDays    = $applyWeeklyLeave ? (int) floor($totalWorkedForLeave / self::WORK_DAYS_PER_LEAVE) : 0;
            
            // خصم ما تم كسبه مسبقاً من الحد الأقصى الشهري لضمان عدم تجاوزه
            $remainingCap     = max(0, $standardMonthlyLeave - $alreadyEarned);
            $cappedEarnedDays = $applyWeeklyLeave ? min($remainingCap, $earnedOffDays) : 0;
            $workRemainder    = $totalWorkedForLeave % self::WORK_DAYS_PER_LEAVE;

            // تغطية مُسبقة للغياب في الفترات القصيرة (Multi-Segment)
            //
            // المشكلة: في Segment أول قصير (مثال: 3 أيام بغياب 1)، لا يكفي وقت
            // لتراكم يوم إجازة بالصيغة (كل 6 أيام عمل = يوم راحة)، مما يُنتج خصماً
            // غير صحيح رغم وجود رصيد إجازة شهري متبقٍّ.
            //
            // الحل: إذا كان الغياب أكبر من الإجازة المكتسبة بالصيغة،
            // وكان هناك رصيد إجازة شهري متبقٍّ، نُسلّف من الرصيد لتغطية الفرق.
            //
            // السلامة:
            //   - الموظف بشهر كامل: earnedOffDays كافٍ → cappedEarnedDays >= absentDays → لا تغيير
            //   - بدون has_auto_weekly_leave: applyWeeklyLeave=false → لا تغيير
            //   - الرصيد مستنفَد: remainingCap = cappedEarnedDays → لا تغيير
            // if ($applyWeeklyLeave && $absentDays > $cappedEarnedDays && $remainingCap > $cappedEarnedDays) {
            //     $cappedEarnedDays += min($remainingCap - $cappedEarnedDays, $absentDays - $cappedEarnedDays);
            // }


            // =================================================================
            // 3. المعادلة الذهبية (الميزان الرقمي)
            // الرصيد = (ما قدمه الموظف + ما استحقه من راحة) - (المطلوب منه في الشهر)
            // =================================================================
            $netBalance = ($actualWorkedDays + $cappedEarnedDays) - $totalMonthDays;

            // 4. ترجمة الميزان إلى (إضافي) أو (خصم)
            $overtimeDays     = 0;
            $totalPenaltyDays = 0;

            if ($netBalance > 0) {
                $overtimeDays = $netBalance;
            } elseif ($netBalance < 0) {
                $totalPenaltyDays = abs($netBalance);
            }

            // 5. تفصيل الخصم (للتوضيح فقط)
            // يُطبَّق فقط عند تفعيل الإجازات الأسبوعية — وإلا فكل الخصم غياب صافٍ
            $leavePenaltyDisplay  = 0;
            $absentPenaltyDisplay = 0;

            if ($totalPenaltyDays > 0) {
                if ($applyWeeklyLeave && $cappedEarnedDays < $standardMonthlyLeave) {
                    $leavePenaltyDisplay = $standardMonthlyLeave - $cappedEarnedDays;
                }
                $absentPenaltyDisplay = max(0, $totalPenaltyDays - $leavePenaltyDisplay);
            }

            $payableDays = $actualWorkedDays + $cappedEarnedDays;

            return [
                'context' => [
                    'is_period_ended'    => $isPeriodEnded,
                    'is_for_payroll'     => $isForPayroll,
                    'weekly_leave_applied' => $applyWeeklyLeave,
                ],
                'inputs' => [
                    'total_days'  => $totalMonthDays,
                    'absent_days' => $absentDays,
                ],
                'analysis' => [
                    'worked_days'       => $actualWorkedDays,
                    'already_earned'    => $alreadyEarned,
                    'earned_leave_days' => $cappedEarnedDays,
                    'work_remainder'    => $workRemainder,
                ],
                'result' => [
                    'leave_penalty'        => $leavePenaltyDisplay,
                    'final_absent_penalty' => $absentPenaltyDisplay,
                    'total_deduction_days' => $totalPenaltyDays,
                    'overtime_days'        => $overtimeDays,
                    'payable_days'         => $payableDays,
                ],
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}