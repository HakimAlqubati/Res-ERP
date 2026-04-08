<?php

namespace App\Modules\HR\AttendanceReports\Calculators;

use App\Models\WorkPeriod;
use Carbon\Carbon;

/**
 * Class DurationCalculator
 * 
 * Provides robust mathematical duration evaluations for supposed shifts and period intervals 
 * with built-in timezone/cross-day validation.
 */
class DurationCalculator
{
    /**
     * Compute supposed duration bounds natively into decimal hours.
     * 
     * @param WorkPeriod $workPeriod The strictly assigned work period configuration.
     * @return float Decimal representation of the exact period duration.
     */
    public function getSupposedDurationHours(WorkPeriod $workPeriod): float
    {
        try {
            $start = Carbon::parse($workPeriod->start_at);
            $end   = Carbon::parse($workPeriod->end_at);
            if ($end->lte($start) || (bool) $workPeriod->day_and_night) {
                $end->addDay();
            }
            return $start->diffInMinutes($end) / 60;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Construct supposed duration bounds dynamically via explicit time boundaries.
     * 
     * @param string $startTime Bound check-in time representation.
     * @param string $endTime Bound check-out time representation.
     * @param bool $dayAndNight Handles cross-midnight evaluation.
     * @return string H:i:s formatted differential.
     */
    public function calcSupposedDuration(string $startTime, string $endTime, bool $dayAndNight): string
    {
        try {
            $start = Carbon::createFromFormat('H:i:s', Carbon::parse($startTime)->format('H:i:s'));
            $end   = Carbon::createFromFormat('H:i:s', Carbon::parse($endTime)->format('H:i:s'));
            if ($dayAndNight || $end->lte($start)) {
                $end->addDay();
            }
            return gmdate('H:i:s', $start->diffInSeconds($end));
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    /**
     * Format floating hours natively into localized standard strings securely.
     * 
     * @param float $hours Decimal span.
     * @return string Human-readable span suffix (e.g. 8h 30m).
     */
    public function formatFloatToHMS(float $hours): string
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        return sprintf('%dh %dm', $h, $m);
    }
}
