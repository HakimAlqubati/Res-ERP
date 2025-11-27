<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Employee;
use App\Models\Setting;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ShiftResolver
{
    /**
     * Resolve the most appropriate shift for the given time.
     */
    public function resolve(Employee $employee, Carbon $time): ?array
    {
        // 1. Get all active periods for the employee around this date
        // We look at Yesterday, Today, Tomorrow to cover all overnight cases
        $candidates = $this->getCandidatePeriods($employee, $time);

        if ($candidates->isEmpty()) {
            return null;
        }

        $bestMatch = null;
        $minDistance = 999999;

        foreach ($candidates as $candidate) {
            $bounds = $this->calculateBounds($candidate['period'], $candidate['date']);

            // Check if time is within the "Grace Window" (Start - BeforeGrace) to (End + AfterGrace)
            if ($time->betweenIncluded($bounds['windowStart'], $bounds['windowEnd'])) {

                // If we are in the window, this is a strong candidate.
                // We prefer the one where we are strictly INSIDE the shift hours if possible,
                // or the one closest to the start time if it's a check-in scenario (but we don't know type yet).

                // For now, return the first valid match found in the window.
                // Logic can be enhanced to handle overlapping shifts better.
                return [
                    'period' => $candidate['period'],
                    'date'   => $candidate['date'],
                    'day'    => $candidate['day'],
                    'bounds' => $bounds
                ];
            }
        }

        return null;
    }

    private function getCandidatePeriods(Employee $employee, Carbon $refTime): Collection
    {
        $dates = [
            $refTime->copy()->subDay()->toDateString(), // Yesterday (for overnight shifts ending today)
            $refTime->toDateString(),                   // Today
            $refTime->copy()->addDay()->toDateString(), // Tomorrow (for shifts starting late today/early tomorrow)
        ];

        $candidates = collect();

        // Eager load periods to avoid N+1
        $employee->loadMissing('employeePeriods.workPeriod', 'employeePeriods.days');

        foreach ($dates as $date) {
            $dayName = strtolower(Carbon::parse($date)->format('D'));

            foreach ($employee->employeePeriods as $ep) {
                // Check Date Range
                if ($ep->start_date > $date || ($ep->end_date && $ep->end_date < $date)) {
                    continue;
                }

                // Check Day of Week
                $hasDay = $ep->days->contains('day_of_week', $dayName);
                if (!$hasDay) {
                    continue;
                }

                if ($ep->workPeriod) {
                    $candidates->push([
                        'period' => $ep->workPeriod,
                        'date'   => $date,
                        'day'    => $dayName
                    ]);
                }
            }
        }

        return $candidates;
    }

    public function calculateBounds(WorkPeriod $period, string $shiftDate): array
    {
        $startStr = $period->start_at; // H:i:s
        $endStr   = $period->end_at;   // H:i:s

        $shiftStart = Carbon::parse("$shiftDate $startStr");
        $shiftEnd   = Carbon::parse("$shiftDate $endStr");

        // Handle Overnight
        // If end time is less than start time, OR explicit flag is set
        if ($period->day_and_night || $shiftEnd->lt($shiftStart)) {
            // If end time is "smaller" than start time (e.g. 22:00 to 06:00), add a day to end
            if ($shiftEnd->lt($shiftStart)) {
                $shiftEnd->addDay();
            }
        }

        $allowedBefore = (int) Setting::getSetting('hours_count_after_period_before', 0);
        $allowedAfter  = (int) Setting::getSetting('hours_count_after_period_after', 0);

        return [
            'start'       => $shiftStart,
            'end'         => $shiftEnd,
            'windowStart' => $shiftStart->copy()->subHours($allowedBefore),
            'windowEnd'   => $shiftEnd->copy()->addHours($allowedAfter),
        ];
    }
}
