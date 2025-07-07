<?php
namespace App\Services\HR\Attendance;

use App\Models\Setting;
use Carbon\Carbon;

class AttendanceDateService
{
    public static function adjustDateForMidnightShift(string $date, string $time, ?object $closestPeriod): array
    {
        if (! $closestPeriod || ! $closestPeriod->start_at) {
            return ['date' => $date, 'day' => Carbon::parse($date)->format('l')];
        }

        $startAt                 = $closestPeriod->start_at;
        $allowedTimeBeforePeriod = Carbon::createFromFormat('H:i:s', $startAt)
            ->subHours((int) Setting::getSetting('hours_count_after_period_before'))
            ->format('H:i:s');

        if (
            $time >= $allowedTimeBeforePeriod &&
            $time <= '23:59:00' && $startAt === '00:00:00') {$date = Carbon::parse($date)->addDay()->toDateString();}

        $day = Carbon::parse($date)->format('l');

        return ['date' => $date, 'day' => $day];
    }
}