<?php

namespace App\Modules\HR\Payroll\Services;

class WeeklyLeaveCalculator
{
    public static function calculate($requiredDays, $absentDays): array
    {
        $ratio = config('hr.weekly_leave_ratio', 6); // كل كم يوم حضور = 1 إجازة
        $cap   = config('hr.weekly_leave_cap', 4);   // السقف الشهري

        $totalAttendanceDays = max(0, $requiredDays - $absentDays);

        $allowedBase     = (int) intdiv($totalAttendanceDays, $ratio); // <-- بدل intdiv
        // dd($allowedBase);
        $allowedLeaves   = min($allowedBase, $cap);
        $compensatedDays = min($absentDays, $allowedLeaves);
        $excessAbsence   = max(0, $absentDays - $allowedLeaves);
        $remainingLeaves = max(0, $allowedLeaves - $compensatedDays);


        return [
            'final_result' => [
                'compensated_days' => $compensatedDays, // المعتمد النهائي
                'remaining_leaves' => $remainingLeaves,
            ],
            'details' => [
                'required_days'    => $requiredDays,
                'absent_days'      => $absentDays,
                'attendance_days'  => $totalAttendanceDays,
                'allowed_leaves'   => $allowedLeaves,
                'excess_absence'   => $excessAbsence,
            ],
        ];
    }

    public static function calculateLeave(int $absentDays): array
    {
        // عدد أيام الغياب في الشهر
        $absences = $absentDays;

        // 🟢 الرصيد الأساسي المسموح (4 أيام إجازة أسبوعية بالشهر)
        // يتم خصم يوم إجازة مقابل كل 6 أيام غياب
        $earnedBalance = max(0, 4 - intdiv($absences, 6));

        // 🟢 عدد أيام الإجازة التي تحولت لتعويض الغياب
        // كل 6 أيام غياب = تخصم يوم إجازة (حتى 4 أيام كحد أقصى)
        $convertedLeaves = min(4, intdiv($absences, 6));

        // 🟢 إجازة مقدمة (advance leave)
        // يعني لو الموظف غاب أقل أو يساوي الرصيد المستحق → يتم تعويض الغياب مباشرة من رصيده
        $advanceLeave = min($absences, $earnedBalance);

        // 🟢 إجازة فائضة (extra leave)
        // تمثل الفرق: إذا رصيده المستحق أكبر من الغياب → يبقى له رصيد إجازة لم يُستخدم
        $remainingBalance = max(0, $earnedBalance - $absences);

        return [
            'final_result' => [
                // عدد أيام الغياب التي تم تعويضها فعلاً من الإجازات
                'compensated_days' => $advanceLeave,

                // عدد الإجازات التي ما زالت متبقية بعد خصم الغياب
                'remaining_leaves' => $remainingBalance,
            ],
            'details' => [
                'absent_days'     => $absences,         // إجمالي الغياب
                'earned_balance'  => $earnedBalance,    // الرصيد المستحق بعد خصم الغياب
                'leave_converted' => $convertedLeaves,  // الإجازات التي تحولت لغياب
                'advance_leave'   => $advanceLeave,     // إجازة مقدمة لتعويض الغياب
                'extra_leave'     => $remainingBalance, // إجازة زائدة لم تُستخدم
            ],
        ];
    }
}
