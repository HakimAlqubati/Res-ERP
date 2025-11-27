<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class AttendanceCalculator
{
    public function calculateCheckIn(AttendanceContext $ctx): void
    {
        $checkTime = $ctx->requestTime;
        $shiftStart = $ctx->shiftBounds['start'];

        if ($checkTime->lt($shiftStart)) {
            // Early Arrival
            $ctx->earlyArrivalMinutes = $checkTime->diffInMinutes($shiftStart);
            $ctx->status = Attendance::STATUS_EARLY_ARRIVAL;
        } else {
            // Late or On Time
            $diff = $checkTime->diffInMinutes($shiftStart);
            if ($diff > 0) {
                $ctx->delayMinutes = $diff;
                $ctx->status = Attendance::STATUS_LATE_ARRIVAL;
            } else {
                $ctx->status = Attendance::STATUS_ON_TIME;
            }
        }
    }

    public function calculateCheckOut(AttendanceContext $ctx): void
    {
        $checkTime = $ctx->requestTime;
        $shiftEnd = $ctx->shiftBounds['end'];

        // Calculate Duration
        if ($ctx->lastAction) {
            $checkInTime = Carbon::parse($ctx->lastAction->check_date . ' ' . $ctx->lastAction->check_time);

            // Adjust check-in time if it was "yesterday" relative to this checkout logic?
            // Actually, if we rely on DB timestamps, it's easier.
            // But let's stick to the logic:

            // If check-in was "00:00:00" based (legacy issue), we might need fixing.
            // Assuming standard Y-m-d H:i:s format in DB for now or handling it via Carbon.

            $ctx->actualMinutes = $checkInTime->diffInMinutes($checkTime);
        }

        if ($checkTime->gt($shiftEnd)) {
            // Late Departure (Overtime?)
            $ctx->lateDepartureMinutes = $checkTime->diffInMinutes($shiftEnd);
            $ctx->status = Attendance::STATUS_LATE_DEPARTURE;
        } elseif ($checkTime->lt($shiftEnd)) {
            // Early Departure
            $ctx->earlyDepartureMinutes = $checkTime->diffInMinutes($shiftEnd);
            $ctx->status = Attendance::STATUS_EARLY_DEPARTURE;
        } else {
            $ctx->status = Attendance::STATUS_ON_TIME;
        }
    }
}
