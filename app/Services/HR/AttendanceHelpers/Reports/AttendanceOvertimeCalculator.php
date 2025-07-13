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
        $approvedOvertimeDB = $employee->overtimesByDate($date)// احذف هذا السطر إذا ما عندك period_id بجدول الاوفر تايم
            ->sum('hours');                            // تأكد أن الساعات مخزنة كـ float

// 5. تطبيق نفس لوجيك الهيلبر
        if ($isActualLargerThanSupposed && $approvedOvertimeDB > 0) {
// لو الموظف عمل أوفر تايم ومسجّل بنظام الاوفر تايم
            return $this->formatFloatToDuration($approvedOvertimeDB + ($actualHours - $supposedDuration));
        } elseif ($isActualLargerThanSupposed && $approvedOvertimeDB == 0) {
// عمل أكثر من المفترض، ولا يوجد سجل أوفر تايم (أو غير معتمد)
            return $this->formatFloatToDuration($actualHours - $supposedDuration);
        } else {
// لم يتجاوز الوقت المفترض
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
        return 0;
    }

/**
 * تحويل العشري لصيغة ساعات:دقائق (مثال: 2.5 → "2:30")
 */
    public function formatFloatToDuration($hours)
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        return sprintf("%d:%02d", $h, $m);
    }
}