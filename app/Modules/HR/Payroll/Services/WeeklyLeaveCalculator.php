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
        // Simple Logic: 
        // Max Monthly Balance = 4 days
        // Remaining Balance = Max (4) - Absent Days
        // If absent days > 4, remaining is 0.

        $maxBalance = 4;
        $remainingBalance = max(0, $maxBalance - $absentDays);

        return [
            'final_result' => [
                'compensated_days' => 0, // Not used in this context essentially
                'remaining_leaves' => $remainingBalance,
            ],
            'details' => [
                'absent_days'     => $absentDays,
                'earned_balance'  => $maxBalance,
                'leave_converted' => 0,
                'advance_leave'   => 0,
                'extra_leave'     => $remainingBalance,
            ],
        ];
    }
}
