<?php

namespace App\Services\HR\Attendance;

use Carbon\Carbon;

class PeriodHelper
{
    public function calculateHourDifference(string $time1, string $time2, string $date): float
    {
        $dt1 = Carbon::parse("{$date} {$time1}");
        $dt2 = Carbon::parse("{$date} {$time2}");
        return $dt1->floatDiffInRealHours($dt2);
    }
}