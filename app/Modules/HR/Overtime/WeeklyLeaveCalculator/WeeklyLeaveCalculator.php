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
     * دالة الاحتساب الرقمي للإجازات والإضافي
     * * @param int $totalMonthDays إجمالي أيام الشهر (الوعاء الزمني)
     * @param int $absentDays عدد أيام الغياب
     * @return array
     */
    public function calculate(int $totalMonthDays, int $absentDays): array
    {
        try {
            if ($absentDays > $totalMonthDays) {
                // لتجنب الأخطاء الحسابية، نعتبر الغياب يساوي أيام الشهر كحد أقصى
                $absentDays = $totalMonthDays;
            }

            // 1. حساب أيام العمل الصافية
            $actualWorkedDays = $totalMonthDays - $absentDays;

            // 2. حساب رصيد الراحة المكتسب بناءً على الجهد
            $earnedOffDays = floor($actualWorkedDays / self::WORK_DAYS_PER_LEAVE);

            // 3. تحديد الاستحقاق المعتمد للحسابات المالية (لا يتجاوز الحد القياسي)
            $cappedEarnedDays = min(self::STANDARD_MONTHLY_LEAVE, $earnedOffDays);

            // لحساب كسور العمل (لغرض التحليل والعرض فقط)
            $workRemainder = $actualWorkedDays % self::WORK_DAYS_PER_LEAVE;

            // =================================================================
            // 4. المعادلة الذهبية (الميزان الرقمي)
            // الرصيد = (ما قدمه الموظف + ما استحقه من راحة) - (المطلوب منه في الشهر)
            // =================================================================

            $netBalance = ($actualWorkedDays + $cappedEarnedDays) - $totalMonthDays;

            // 5. ترجمة الميزان إلى (إضافي) أو (خصم)
            $overtimeDays = 0;
            $totalPenaltyDays = 0; // هذا هو الرقم الإجمالي للخصم

            if ($netBalance > 0) {
                // كفة الموظف راجحة (له إضافي)
                $overtimeDays = $netBalance;
            } elseif ($netBalance < 0) {
                // كفة الموظف خاسرة (عليه خصم)
                $totalPenaltyDays = abs($netBalance);
            }

            // 6. تفصيل الخصم (للتوضيح فقط)
            $leavePenaltyDisplay = 0;
            $absentPenaltyDisplay = 0;

            if ($totalPenaltyDays > 0) {
                // أ. هل نقص استحقاقه عن الحد القياسي؟
                if ($cappedEarnedDays < self::STANDARD_MONTHLY_LEAVE) {
                    $leavePenaltyDisplay = self::STANDARD_MONTHLY_LEAVE - $cappedEarnedDays;
                }

                // ب. باقي الخصم يعتبر غياباً صافياً
                $absentPenaltyDisplay = max(0, $totalPenaltyDays - $leavePenaltyDisplay);
            }
            $payableDays = $actualWorkedDays + $cappedEarnedDays;
            return [
                'inputs' => [
                    'total_days'  => $totalMonthDays,
                    'absent_days' => $absentDays
                ],
                'analysis' => [
                    'worked_days'       => $actualWorkedDays,
                    'earned_leave_days' => min(self::STANDARD_MONTHLY_LEAVE, $earnedOffDays),
                    'work_remainder'    => $workRemainder,
                ],
                'result' => [
                    'leave_penalty'        => $leavePenaltyDisplay,
                    'final_absent_penalty' => $absentPenaltyDisplay,

                    // ==========================================
                    // (تمت الإضافة) إجمالي أيام الخصم النهائي
                    // ==========================================
                    'total_deduction_days' => $totalPenaltyDays,

                    'overtime_days'        => $overtimeDays,
                    'payable_days'         => $payableDays
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
