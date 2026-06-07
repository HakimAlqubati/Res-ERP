<?php

namespace App\Models\Traits;

use App\Models\LeaveType;

trait OldScopesLeaveType
{
    /**
     * Scope to get the sum of monthly count days, defaulting null values to 4.
     *
     * @param $query
     * @return int
     */
    public function scopeGetMonthlyCountDaysSum($query)
    {
        return $query->where('type', LeaveType::TYPE_WEEKLY)
            ->where('balance_period', LeaveType::BALANCE_PERIOD_MONTHLY)
            ->get()
            ->sum(function ($leaveType) {
                return $leaveType->count_days ?? 4;
            });
    }

    public function scopeWeeklyLeave($query)
    {
        return $query->where('type', LeaveType::TYPE_WEEKLY)
            ->where('balance_period', LeaveType::BALANCE_PERIOD_MONTHLY)
            ->where('active', 1)->first()
        ;
    }
}
