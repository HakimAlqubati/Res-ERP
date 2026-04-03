<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Employee;
use App\Models\WorkPeriod;

class AttendanceOvertimeCalculator
{
    /**
     * احتساب الأوفر تايم لكل فترة في يوم معين لموظف.
     * يرجع قيمة approved_overtime بالفورمات المفضل (float أو نص ساعات:دقائق)
     */
    public function calculatePeriodApprovedOvertime(Employee $employee, $period, $date)
    {
        // 1. اجلب إجمالي الساعات الفعلية للعمل بهذه الفترة (بالعشري)
        $actualHours = $this->parseDurationToFloat($employee->calculateTotalWorkHours($period['period_id'], $date) ?? 0);

        // 2. مدة الدوام المفترضة للفترة (بالعشري)
        $periodObject     = WorkPeriod::find($period['period_id']);
        $supposedDuration = $this->parseDurationToFloat($periodObject?->supposed_duration ?? 0);

        // 3. هل الموظف تجاوز الوقت المفترض؟
        $isActualLargerThanSupposed = $actualHours > $supposedDuration;

        // 4. اجلب الأوفر تايم المعتمد لليوم والفترة
        $approvedOvertimeDB = $employee->overtimesByDate($date) // احذف هذا السطر إذا ما عندك period_id بجدول الاوفر تايم
            ->sum('hours');                                         // تأكد أن الساعات مخزنة كـ float

        // 5. تطبيق نفس لوجيك الهيلبر
        if ($isActualLargerThanSupposed && $approvedOvertimeDB > 0) {
            // لو الموظف عمل أوفر تايم ومسجّل بنظام الاوفر تايم
            $res = $this->formatFloatToDuration($approvedOvertimeDB + ($supposedDuration));
            // dd(
            //     $approvedOvertimeDB,
            //     $actualHours,
            //     $supposedDuration,
            //     $res
            // );
            return $res;
        } elseif ($isActualLargerThanSupposed && $approvedOvertimeDB == 0) {
            return $this->formatFloatToDuration($supposedDuration);
        } else {
            if (is_numeric($actualHours) && $actualHours > 0) {
                return $this->formatFloatToDuration($actualHours);
            }
            return $this->formatFloatToDuration(0);
        }
    }

    /**
     * تحويل مدة بصيغة ساعات:دقائق إلى float (مثال: "2:30" → 2.5)
     */
    public function parseDurationToFloat($duration)
    {
        if (is_numeric($duration)) {
            return (float) $duration;
        }

        if (is_string($duration) && strpos($duration, ':') !== false) {
            [$h, $m] = explode(':', $duration);
            return (float) $h + ((float) $m / 60);
        }

        if (is_string($duration) && preg_match('/(\d+)\s*h\s*(\d+)\s*m/', $duration, $matches)) {
            $h = (float) $matches[1];
            $m = (float) $matches[2];
            return $h + ($m / 60);
        }

        return 0;
    }

    /**
     * تحويل العشري لصيغة ساعات:دقائق (مثال: 2.5 → "2:30")
     */
    public function formatFloatToDuration($hours)
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        return sprintf('%dh %dm', $h, $m);
    }
}
