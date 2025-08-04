<?php
namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class AttendanceFetcher
{
    public static function getExistingAttendance($employee, $closestPeriod, $date, $day, $currentCheckTime)
    { 
        $attendances = Attendance::where('employee_id', $employee->id)
            ->where('period_id', $closestPeriod->id) // Using array key if closestPeriod is an array
            ->where('check_date', $date)
            ->where('accepted', 1)
            ->where('day', $day)
            ->select('check_type', 'check_date')
            ->get();
        if ($attendances->count() === 0) {
            $previousDate    = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');
            $previousDayName = \Carbon\Carbon::parse($date)->subDay()->format('l');

            $attendanceInPreviousDay = Attendance::where('employee_id', $employee->id)
                ->where('accepted', 1)
                ->where('period_id', $closestPeriod->id)
                ->where('check_date', $previousDate)
                ->latest('id')
                ->first();

            if ($attendanceInPreviousDay) {
                $isLatestSamePeriod = self::checkIfSamePeriod($employee->id, $attendanceInPreviousDay, $closestPeriod, $previousDate, $date, $currentCheckTime);
                if (! $isLatestSamePeriod) {

                    return $attendances?->toArray() ?? [];
                }

                if (($attendanceInPreviousDay->check_type == Attendance::CHECKTYPE_CHECKIN)) {
                    return [
                        'in_previous'       => $attendanceInPreviousDay,
                        'previous_day_name' => $previousDayName,
                        'check_type'        => Attendance::CHECKTYPE_CHECKOUT,
                    ];
                } else {
                    return [
                        'in_previous'       => $attendanceInPreviousDay,
                        'previous_day_name' => $previousDayName,
                        'check_type'        => Attendance::CHECKTYPE_CHECKIN,
                    ];
                }
            }

            return $attendances?->toArray() ?? [];
        }
        return $attendances?->toArray() ?? [];
    }

    protected static function checkIfSamePeriod($employeeId, $attendanceInPreviousDay, $period, $date, $currentDate, $checkTime)
    {
        $latestAttendance = Attendance::where('employee_id', $employeeId)
            ->where('accepted', 1)
            ->latest('id')
            ->first();

        if ($latestAttendance && $latestAttendance->period_id == $period->id) {
            $res = self::checkIfattendanceInPreviousDayIsCompleted($attendanceInPreviousDay, $period, $checkTime, $date, $currentDate);
            return ! $res;
        }
        return false;
    }

    public static function checkIfattendanceInPreviousDayIsCompleted($attendanceInPreviousDay, $period, $currentCheckTime, $currentRealDate)
    {
        $previousDate    = $attendanceInPreviousDay?->check_date;
        $periodId        = $attendanceInPreviousDay?->period_id;
        $employeId       = $attendanceInPreviousDay?->employee_id;
        $periodEndTime   = $period->end_at;
        $periodStartTime = $period->start_at;

        $allowedTimeAfterPeriod = Carbon::createFromFormat('H:i:s', $periodEndTime)->addHours((int) Setting::getSetting('hours_count_after_period_after'))->format('H:i:s');

        $latstAttendance = Attendance::where('employee_id', $employeId)
            ->where('accepted', 1)
            ->where('period_id', $periodId)
            ->where('check_date', $previousDate)
            ->select('id', 'check_type', 'check_date', 'check_time', 'is_from_previous_day')
            ->latest('id')
            ->first();

        $lastCheckType = $latstAttendance->check_type;

        $dateTimeString = $attendanceInPreviousDay->check_date . ' ' . $latstAttendance->check_time;
        $lastCheckTime  = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString);

        $dateTimeString  = $currentRealDate . ' ' . $currentCheckTime;
        $currentDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString);

        $diff = self::calculateTimeDifference($periodEndTime, $currentCheckTime, $currentRealDate);

        $lastCheckedPeriodEndTimeDateTime = Carbon::parse($attendanceInPreviousDay->check_date . ' ' . $allowedTimeAfterPeriod);

        if ($currentCheckTime > $periodEndTime) {
            if ($diff >= Setting::getSetting('hours_count_after_period_after')) {
                return true;
            }
        }
        if ($period->day_and_night) {

            if ($lastCheckType == Attendance::CHECKTYPE_CHECKOUT) {
                if ($currentCheckTime >= $periodEndTime) {
                    return true;
                }
            } else {
                if ($currentCheckTime >= $periodStartTime) {
                    return true;
                }
            }
        } else {
            if ($currentDateTime->toTimeString() > $lastCheckedPeriodEndTimeDateTime->toTimeString()) {
                return true;
            }
            if ($currentCheckTime < $periodEndTime && $currentCheckTime > $allowedTimeAfterPeriod) {
                $diff = self::calculateTimeDifference($periodEndTime, $currentCheckTime, $currentRealDate);

                if ($diff >= Setting::getSetting('hours_count_after_period_after')) {
                    return true;
                }
            }
            if ($lastCheckType == Attendance::CHECKTYPE_CHECKOUT) {
                if ($lastCheckTime >= $periodEndTime) {
                    return true;
                }
            } else {
                if ($currentCheckTime >= $periodStartTime) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function calculateTimeDifference(string $currentTime, string $endTime, $date = null): float
    {
        // Create DateTime objects for each time
        $currentDateTime   = Carbon::parse($date . ' ' . $currentTime);
        $periodEndDateTime = Carbon::parse($date . ' ' . $endTime);

        // Calculate the difference
        $diff = $currentDateTime->diff($periodEndDateTime);

                                                  // Get the total difference in hours
        $totalHours = $diff->h + ($diff->i / 60); // Include minutes as a fraction of an hour
                                                  // If the current time is greater than the end time, calculate total hours accordingly
        if ($currentDateTime > $periodEndDateTime) {
            // Circular manner (i.e., next day)
            $totalHours = (24 - $periodEndDateTime->format('H')) + $currentDateTime->format('H') + (($currentDateTime->format('i') - $periodEndDateTime->format('i')) / 60);
        }
        $res = round($totalHours, 2);
        // dd($res);
        return $res;
    }
}