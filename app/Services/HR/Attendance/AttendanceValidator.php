<?php
namespace App\Services\HR\Attendance;

use App\Models\Setting;
use Carbon\Carbon;

class AttendanceValidator
{
    public function checkTimeIfOutOfAllowedAttedance($period, $time)
    {
        $allowedTimeAfterPeriod = Carbon::createFromFormat('H:i:s', $period->end_at)
            ->addHours((int) Setting::getSetting('hours_count_after_period_after'))
            ->format('H:i:s');

        $allowedTimeBeforePeriod = Carbon::createFromFormat('H:i:s', $period->start_at)
            ->subHours((int) Setting::getSetting('hours_count_after_period_before'))
            ->format('H:i:s');
        // dd($time,$allowedTimeBeforePeriod,$allowedTimeAfterPeriod);
        // Check if the time is within the range
        if ($time >= $allowedTimeAfterPeriod && $time <= $allowedTimeBeforePeriod) {
            return true; // $time is within the allowed range
        }

        return false; // $time is outside the allowed range
    }

    public function isTimeOutOfAllowedRange($period, string $time): bool
    {
        $after = Carbon::createFromFormat('H:i:s', $period->end_at)
            ->addHours((int) Setting::getSetting('hours_count_after_period_after'))
            ->format('H:i:s');

        $before = Carbon::createFromFormat('H:i:s', $period->start_at)
            ->subHours((int) Setting::getSetting('hours_count_after_period_before'))
            ->format('H:i:s');
// dd($after,$before);
        // إذا لم يكن الوقت ضمن الفاصل المسموح → اعتبره مرفوض
        if (! ($time >= $before || $time <= $after)) {
            return true;
        }

        return false;
    }

}