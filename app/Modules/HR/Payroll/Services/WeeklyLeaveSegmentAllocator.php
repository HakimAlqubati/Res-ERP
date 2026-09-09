<?php

namespace App\Modules\HR\Payroll\Services;

/**
 * Class WeeklyLeaveSegmentAllocator
 *
 * يوزّع نتيجة "إجازة أسبوعية" واحدة موثوقة (محسوبة مرة واحدة على كامل فترة
 * الموظف عبر كل الفروع) تناسبيًا على كل Segment/فرع — لغرض عرض التكلفة
 * الصحيحة لكل فرع فقط. لا يُعيد حساب الاستحقاق إطلاقًا، فقط يوزّع رقمًا
 * صحيحًا مسبقًا، مما يضمن عدم تجاوز السقف الشهري أو ازدواجية الخصم/البدل
 * بصرف النظر عن عدد الـ Segments أو توزيع الأيام بينها.
 */
class WeeklyLeaveSegmentAllocator
{
    /**
     * @param array $monthlyResult 'weekly_leave_calculation' المحسوبة مرة واحدة على الفترة الكاملة.
     * @param array $segments      قائمة مرتبة ['worked_days' => int, 'absent_days' => int] لكل Segment.
     * @return array               بنفس ترتيب $segments، كل عنصر يحتوي ['result' => [...], 'earned_leave_days' => float].
     */
    public function allocate(array $monthlyResult, array $segments): array
    {
        $count = count($segments);
        if ($count === 0) {
            return [];
        }

        $totals = [
            'total_deduction_days' => (float) ($monthlyResult['result']['total_deduction_days'] ?? 0),
            'overtime_days'        => (float) ($monthlyResult['result']['overtime_days'] ?? 0),
            'leave_penalty'        => (float) ($monthlyResult['result']['leave_penalty'] ?? 0),
            'final_absent_penalty' => (float) ($monthlyResult['result']['final_absent_penalty'] ?? 0),
            'earned_leave_days'    => (float) ($monthlyResult['analysis']['earned_leave_days'] ?? 0),
        ];

        $totalAbsentDays = array_sum(array_column($segments, 'absent_days'));
        $totalWorkedDays = array_sum(array_column($segments, 'worked_days'));

        $running = array_fill_keys(array_keys($totals), 0.0);
        $allocations = [];

        foreach (array_values($segments) as $i => $segment) {
            $isLast = ($i === $count - 1);

            // الخصم وعقوبة الإجازة: تُوزَّع حسب حصة الغياب الفعلي لكل فرع
            $deductionShare = $totalAbsentDays > 0
                ? ($segment['absent_days'] / $totalAbsentDays)
                : (1 / $count);

            // الإضافي (overtime_days): يُوزَّع حسب حصة أيام العمل الفعلي لكل فرع
            $overtimeShare = $totalWorkedDays > 0
                ? ($segment['worked_days'] / $totalWorkedDays)
                : (1 / $count);

            $shares = [
                'total_deduction_days' => $deductionShare,
                'leave_penalty'        => $deductionShare,
                'final_absent_penalty' => $deductionShare,
                'overtime_days'        => $overtimeShare,
                'earned_leave_days'    => $deductionShare,
            ];

            $values = [];
            foreach ($totals as $key => $total) {
                if ($isLast) {
                    // الفرع الأخير يأخذ الباقي بالضبط — يضمن تطابق المجموع الكلي دون فروق تقريب
                    $values[$key] = round($total - $running[$key], 4);
                } else {
                    $values[$key] = round($total * $shares[$key], 4);
                    $running[$key] += $values[$key];
                }
            }

            $allocations[] = [
                'earned_leave_days' => $values['earned_leave_days'],
                'result' => [
                    'leave_penalty'        => $values['leave_penalty'],
                    'final_absent_penalty' => $values['final_absent_penalty'],
                    'total_deduction_days' => $values['total_deduction_days'],
                    'overtime_days'        => $values['overtime_days'],
                    'payable_days'         => $segment['worked_days'] + $values['earned_leave_days'],
                ],
            ];
        }

        return $allocations;
    }
}