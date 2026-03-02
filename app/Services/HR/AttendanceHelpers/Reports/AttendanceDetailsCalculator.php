<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use Carbon\Carbon;

class AttendanceDetailsCalculator
{
    /**
     * Calculate total worked duration for a period by pairing checkins with checkouts.
     *
     * @param array $checkIns  Array of checkin records, each with 'check_time' key (H:i:s)
     * @param array $checkOuts Array of checkout records, each with 'check_time' key (H:i:s)
     * @return array ['total_minutes' => int, 'formatted' => string]
     */
    public static function calculatePeriodDuration(array $checkIns, array $checkOuts): array
    {
        $totalMinutes = 0;
        $maxRows = max(count($checkIns), count($checkOuts));

        for ($i = 0; $i < $maxRows; $i++) {
            $ciVal = $checkIns[$i]['check_time'] ?? null;
            $coVal = $checkOuts[$i]['check_time'] ?? null;

            if ($ciVal && $coVal) {
                try {
                    $ciTime = Carbon::createFromFormat('H:i:s', $ciVal);
                    $coTime = Carbon::createFromFormat('H:i:s', $coVal);

                    if ($coTime->lessThan($ciTime)) {
                        $coTime->addDay();
                    }

                    $totalMinutes += $ciTime->diffInMinutes($coTime);
                } catch (\Exception $e) {
                    // Skip invalid time formats
                }
            }
        }

        if ($totalMinutes > 0) {
            $h = intdiv($totalMinutes, 60);
            $m = $totalMinutes % 60;
            $formatted = "{$h}h {$m}m";
        } else {
            $formatted = '-';
        }

        return [
            'total_minutes' => $totalMinutes,
            'formatted'     => $formatted,
        ];
    }

    /**
     * Calculate detailed breakdown of attendance for the details modal.
     * Groups checkins/checkouts by period, pairs them, and calculates per-pair durations.
     *
     * @param array $data Raw attendance details (each with 'check_type', 'check_time', 'period_id')
     * @return array ['attendances' => [...], 'total_hours' => int, 'total_minutes' => int]
     */
    public static function calculateDetailedBreakdown(array $data): array
    {
        $attendances = [];
        $totalMinutes = 0;

        // Group by period_id
        foreach ($data as $detail) {
            if ($detail['check_type'] === 'checkin') {
                $attendances[$detail['period_id']]['checkins'][] = $detail['check_time'];
            } elseif ($detail['check_type'] === 'checkout') {
                $attendances[$detail['period_id']]['checkouts'][] = $detail['check_time'];
            }
        }

        // Calculate hours between each checkin/checkout pair
        foreach ($attendances as $index => $attendance) {
            $maxRows = max(count($attendance['checkins'] ?? []), count($attendance['checkouts'] ?? []));

            for ($i = 0; $i < $maxRows; $i++) {
                $checkin = $attendance['checkins'][$i] ?? null;
                $checkout = $attendance['checkouts'][$i] ?? null;

                if ($checkin && $checkout) {
                    $checkinTime = Carbon::createFromFormat('H:i:s', $checkin);
                    $checkoutTime = Carbon::createFromFormat('H:i:s', $checkout);

                    if (!$checkoutTime->greaterThanOrEqualTo($checkinTime)) {
                        $checkoutTime->addDay();
                    }

                    $minutesDifference = $checkinTime->diffInMinutes($checkoutTime);
                    $hours = intdiv($minutesDifference, 60);
                    $minutes = $minutesDifference % 60;

                    $attendances[$index]['total_hours'][$i] = "{$hours}h {$minutes}m";
                    $totalMinutes += $minutesDifference;
                } else {
                    $attendances[$index]['total_hours'][$i] = '-';
                }
            }
        }

        $totalHours = intdiv($totalMinutes, 60);
        $remainingMinutes = $totalMinutes % 60;

        return [
            'attendances'       => $attendances,
            'total_hours'       => $totalHours,
            'remaining_minutes' => $remainingMinutes,
            'total_minutes'     => $totalMinutes,
        ];
    }
}
