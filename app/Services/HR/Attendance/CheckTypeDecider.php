<?php

namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class CheckTypeDecider
{
    public function decide(
        $employee,
        $closestPeriod,
        string $date,
        string $time,
        string $day,
        $existAttendance,
        bool &$typeHidden,
        string $manualType = null,
    ): array | string {
        // $attendanceCount      = $existAttendance->count();
        $attendanceCollection = collect($existAttendance);

        $attendanceCount = $attendanceCollection->count();



        if ($attendanceCount === 0) {
            $allowedTimeBeforePeriod = Carbon::createFromFormat('H:i:s', $closestPeriod->start_at)
                ->subHours((int) Setting::getSetting('hours_count_after_period_before'))
                ->format('H:i:s');

            if (
                $closestPeriod->start_at === '00:00:00' &&
                $time >= $allowedTimeBeforePeriod &&
                $time <= '23:59:00'
            ) {
                $diff = false;
            } else {
                $diff = $this->isWithinPreEndAllowance($time, $date, $closestPeriod);
            }

            if ($diff) {

                if ($manualType !== null && $manualType !== '') {
                    return $manualType;
                }
                if ($typeHidden) {
                    $typeHidden = false;
                    $message    = 'please specify type ';
                    // Attendance::storeNotAccepted($employee, $date, $time, $day, $message, $closestPeriod->id, Attendance::ATTENDANCE_TYPE_RFID);
                    return $message;
                } elseif (! $typeHidden && $manualType !== '') {
                    $typeHidden = true;
                    return $manualType;
                } else {
                    $message = 'please specify type also';
                    // Attendance::storeNotAccepted($employee, $date, $time, $day, $message, $closestPeriod->id, Attendance::ATTENDANCE_TYPE_RFID);
                    return $message;
                }
            }

            return Attendance::CHECKTYPE_CHECKIN;
        }
        // if ($attendanceCount == 1 && $existAttendance[0]['check_type'] == Attendance::CHECKTYPE_CHECKOUT) {
        //     return Attendance::CHECKTYPE_CHECKIN;
        // }
        // dd($attendanceCount,$existAttendance,$attendanceCollection);
        // فردي أو زوجي
        return $attendanceCount % 2 === 0
            ? Attendance::CHECKTYPE_CHECKIN
            : Attendance::CHECKTYPE_CHECKOUT;
    }

    private function isWithinPreEndAllowance(string $currentTime, string $date, $period): bool
    {
        $endTime       = $period->end_at;
        $isDayAndNight = $period->day_and_night;

        $currentDateTime   = Carbon::parse("$date $currentTime");
        $periodEndDateTime = Carbon::parse("$date $endTime");

        if ($isDayAndNight) {
            $periodEndDateTime = $periodEndDateTime->addDay();
        }

        $diffWithEndPeriod = $currentDateTime->diffInHours($periodEndDateTime);
        return $diffWithEndPeriod <= Setting::getSetting('pre_end_hours_for_check_in_out');
    }
}
