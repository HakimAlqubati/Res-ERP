<?php
namespace App\Services\HR\Attendance;

use App\Models\EmployeePeriod;
use Carbon\Carbon;

class PeriodHelper
{
    public function calculateHourDifference(string $time1, string $time2, string $date): float
    {
        $dt1 = Carbon::parse("{$date} {$time1}");
        $dt2 = Carbon::parse("{$date} {$time2}");
        return $dt1->floatDiffInRealHours($dt2);
    }

    public function isTimeInPeriod($currentTime, $end, $dayAndNight)
    {
        // لاحظ: كل الأوقات هنا بالثواني منذ منتصف الليل (strtotime('H:i:s'))
        if ($dayAndNight) {
            // فترة ليلية (تعبر منتصف الليل)
            // مثال: start=22:00 (79200) end=03:00 (10800) currentTime=02:00 (7200)
            return ($currentTime >= strtotime('00:00:00')) && ($currentTime <= $end);
        }
        return false;
    }

    public function periodCoversDate($period, $date)
    {
        foreach ($period->days as $dayRow) {

            $isDayOk  = $dayRow->day_of_week === strtolower(Carbon::parse($date)->format('D'));
            $isDateOk = $dayRow->start_date <= $date && (! $dayRow->end_date || $dayRow->end_date >= $date);
            if ($isDayOk && $isDateOk) {
                return true;
            }
        }
        return false;
    }

    function hasWorkPeriodForDate(int $employeeId, int $periodId, string $date, string $dayOfWeek): bool
    {
        // نحاول جلب فترة العمل للموظف مع الأيام المرتبطة بها
        $employeePeriod = EmployeePeriod::with(['days'])
            ->where('employee_id', $employeeId)
            ->where('period_id', $periodId)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->first();

        if (! $employeePeriod) {
            return false; // لا توجد فترة بهذا التاريخ
        }

        // تحقق من الأيام المرتبطة بهذه الفترة
        foreach ($employeePeriod->days as $day) {
            $isSameDay = strtolower($day->day_of_week) === strtolower($dayOfWeek);

            $isWithinDateRange =
            Carbon::parse($employeePeriod->start_date)->lte($date) &&
                (
                is_null($employeePeriod->end_date) ||
                Carbon::parse($employeePeriod->end_date)->gte($date)
            );

            if ($isSameDay && $isWithinDateRange) {
                return true;
            }
        }

        return false;
    }

}