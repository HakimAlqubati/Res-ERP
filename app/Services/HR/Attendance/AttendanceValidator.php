<?php

namespace App\Services\HR\Attendance;

use Carbon\Carbon;

class AttendanceValidator
{
    public function isWithinAllowedTime($period, $time): bool
    {
        $allowedAfter = Carbon::createFromFormat('H:i:s', $period->end_at)
            ->addHours(setting('hours_count_after_period_after'))->format('H:i:s');

        $allowedBefore = Carbon::createFromFormat('H:i:s', $period->start_at)
            ->subHours(setting('hours_count_after_period_before'))->format('H:i:s');

        return !($time >= $allowedAfter && $time <= $allowedBefore);
    }
}