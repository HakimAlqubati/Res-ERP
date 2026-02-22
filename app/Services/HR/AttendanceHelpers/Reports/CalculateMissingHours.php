<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Attendance;

class CalculateMissingHours
{
    public function calculate(
        $status,
        $supposedDuration,
        $approvedOvertime,
        $date,
        $employeeId
    ) {
        $attendances = Attendance::where('check_date', $date)
            ->where('employee_id', $employeeId)
            ->where('accepted', 1)
            ->whereNotNull('check_time')
            ->orderBy('check_time')
            ->get();

        $checkins = $attendances->where('check_type', Attendance::CHECKTYPE_CHECKIN)->values();
        $checkouts = $attendances->where('check_type', Attendance::CHECKTYPE_CHECKOUT)->values();

        // If there's 1 or fewer check-ins, there are no gaps between shifts
        if ($checkins->count() <= 1) {
            return [
                'formatted' => '0 h 0 m',
                'total_hours' => 0,
                'total_minutes' => 0,
                'is_multiple' => false,
            ];
        }

        $totalMissingMinutes = 0;

        // Loop through multiple check-ins to find the gap between the previous checkout and current checkin
        for ($i = 1; $i < $checkins->count(); $i++) {
            $currentCheckin = $checkins[$i];

            // Find the most recent checkout before this checkin
            $previousCheckout = $checkouts->where('check_time', '<', $currentCheckin->check_time)->last();

            if ($previousCheckout) {
                // Calculate gap between previous checkout and current checkin
                $checkoutTime = \Carbon\Carbon::parse($date . ' ' . $previousCheckout->check_time);
                $checkinTime = \Carbon\Carbon::parse($date . ' ' . $currentCheckin->check_time);

                // If checkin is on the next day (night shift scenario), adjust the date
                if ($checkinTime->lt($checkoutTime)) {
                    $checkinTime->addDay();
                }

                $gapMinutes = $checkinTime->diffInMinutes($checkoutTime);
                $totalMissingMinutes += $gapMinutes;
            }
        }

        if ($totalMissingMinutes == 0) {
            return [
                'formatted' => '0 h 0 m',
                'total_hours' => 0,
                'total_minutes' => 0,
                'is_multiple' => true,
            ];
        }

        $totalHours = round($totalMissingMinutes / 60, 1);
        $hoursPart = floor($totalMissingMinutes / 60);
        $minutesPart = $totalMissingMinutes % 60;

        return [
            'formatted' => "{$hoursPart} h {$minutesPart} m",
            'total_minutes' => $totalMissingMinutes,
            'total_hours' => $totalHours,
            'is_multiple' => true,
        ];
    }
}
