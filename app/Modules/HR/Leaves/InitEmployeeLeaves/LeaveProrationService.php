<?php

namespace App\Modules\HR\Leaves\InitEmployeeLeaves;

use App\Models\LeaveType;
use Carbon\Carbon;

/**
 * Class LeaveProrationService
 * 
 * Responsible for calculating the exact entitled leave days for an employee, 
 * applying proration logic based on their joining date.
 * 
 * @package App\Modules\HR\Leaves\InitEmployeeLeaves
 */
class LeaveProrationService
{
    /**
     * Calculate the entitled days for a specific period.
     *
     * @param LeaveType $leaveType
     * @param string    $joinDateString
     * @param int       $targetYear
     * @param int|null  $targetMonth
     * @return float
     */
    public static function calculateEntitlement(
        LeaveType $leaveType,
        string $joinDateString,
        int $targetYear,
        ?int $targetMonth = null
    ): float {
        $countDays = (float) $leaveType->count_days;

        if (!$leaveType->prorate_on_hire) {
            return $countDays;
        }

        $joinDate = Carbon::parse($joinDateString)->startOfDay();
        $period = self::resolvePeriodBoundaries($leaveType, $targetYear, $targetMonth);

        // Employee joined before the period started -> Full entitlement
        if ($joinDate->lte($period['start'])) {
            return $countDays;
        }

        // Employee joined after the period ended -> Zero entitlement
        if ($joinDate->gt($period['end'])) {
            return 0.0;
        }

        return self::computeProratedDays($joinDate, $period['start'], $period['end'], $countDays);
    }

    /**
     * Determine the start and end dates of the evaluation period.
     *
     * @param LeaveType $leaveType
     * @param int       $year
     * @param int|null  $month
     * @return array{start: Carbon, end: Carbon}
     */
    private static function resolvePeriodBoundaries(LeaveType $leaveType, int $year, ?int $month): array
    {
        if ($leaveType->balance_period === LeaveType::BALANCE_PERIOD_MONTHLY && $month) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
        } else {
            $start = Carbon::create($year, 1, 1)->startOfYear();
            $end = $start->copy()->endOfYear();
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Execute the mathematical proration formula.
     *
     * @param Carbon $joinDate
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param float  $totalCountDays
     * @return float
     */
    private static function computeProratedDays(Carbon $joinDate, Carbon $periodStart, Carbon $periodEnd, float $totalCountDays): float
    {
        $totalDaysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
        $workedDaysInPeriod = $joinDate->diffInDays($periodEnd) + 1;

        $proratedValue = ($workedDaysInPeriod / $totalDaysInPeriod) * $totalCountDays;

        // Round to the nearest 0.5 (Half-day precision)
        return round($proratedValue * 2) / 2;
    }
}
