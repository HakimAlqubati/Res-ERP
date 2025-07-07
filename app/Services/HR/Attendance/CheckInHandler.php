<?php

namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class CheckInHandler
{
    public function handle(array $attendanceData, $nearestPeriod, Carbon $checkTime, string $checkTimeStr, string $day, $previousRecord = null, $type = ''): array|string
    {
        $date = $attendanceData['check_date'];
        $employee = $attendanceData['employee'];

        $periodEndTime = $nearestPeriod->end_at;
        $periodStartTime = $nearestPeriod->start_at;

        $diff = $this->calculateTimeDifferenceV3($checkTime->toTimeString(), $nearestPeriod, $date);

        if (! $this->isWithinAllowedBeforePeriod($nearestPeriod, $date, $checkTimeStr) && $periodStartTime !== '00:00:00') {
            $message = __('notifications.attendance_out_of_range_before_period');
            Attendance::storeNotAccepted($employee, $date, $checkTimeStr, $day, $message, $nearestPeriod->id, $attendanceData['attendance_type']);
            return $message;
        }

        if (
            $checkTime->toTimeString() < $periodStartTime &&
            $diff > Setting::getSetting('hours_count_after_period_after') && $type === ''
        ) {
            $message = __('notifications.you_cannot_attendance_before') . ' ' . $diff . ' ' . __('notifications.hours');
            Attendance::storeNotAccepted($employee, $date, $checkTimeStr, $day, $message, $nearestPeriod->id, $attendanceData['attendance_type']);
            return $message;
        }

        // شفت ليلي (اليوم السابق)
        if (
            $periodStartTime > $periodEndTime &&
            $checkTimeStr >= '00:00:00' && $checkTimeStr < $periodEndTime &&
            is_null($previousRecord)
        ) {
            $prevDate = Carbon::parse($date)->subDay();
            $attendanceData['check_date'] = $prevDate->toDateString();
            $attendanceData['day'] = $prevDate->format('l');
        }

        // إن وُجد سجل سابق
        if ($previousRecord) {
            $attendanceData['is_from_previous_day'] = 1;
            $attendanceData['check_date'] = $previousRecord['in_previous']?->check_date;
        }

        $attendanceData = array_merge(
            $attendanceData,
            $this->storeCheckIn($nearestPeriod, $checkTime, $attendanceData['check_date'])
        );

        return $attendanceData;
    }

    protected function isWithinAllowedBeforePeriod($period, $date, $time): bool
    {
        $allowedTime = Carbon::createFromFormat('H:i:s', $period->start_at)
            ->subHours((int) Setting::getSetting('hours_count_after_period_before'))
            ->format('H:i:s');

        $checkTime = Carbon::parse("$date $time");
        $allowed = Carbon::parse("$date $allowedTime");

        if ($period->day_and_night) {
            $allowed->subDay();
        }

        return $checkTime->gt($allowed);
    }

    protected function calculateTimeDifferenceV3(string $currentTime, $period, string $date): float
    {
        $startTime = $period->start_at;

        $checkTime = Carbon::parse("$date $currentTime");

        if ($period->day_and_night && $currentTime >= '00:00:00' && $currentTime <= $period->end_at) {
            $date = Carbon::parse($date)->subDay()->toDateString();
        }

        $startDateTime = Carbon::parse("$date $startTime");

        return round($startDateTime->diffInMinutes($checkTime) / 60, 2);
    }

    protected function storeCheckIn($period, Carbon $checkTime, string $date): array
    {
        $startTime = Carbon::parse("$date {$period->start_at}");
        $earlyLimit = Setting::getSetting('early_attendance_minutes');

        if ($checkTime->lt($startTime)) {
            $early = $checkTime->diffInMinutes($startTime);
            return [
                'delay_minutes' => 0,
                'early_arrival_minutes' => $early,
                'status' => $early >= $earlyLimit ? Attendance::STATUS_EARLY_ARRIVAL : Attendance::STATUS_ON_TIME,
            ];
        }

        $late = $startTime->diffInMinutes($checkTime);
        $status = $late <= $earlyLimit ? Attendance::STATUS_ON_TIME : Attendance::STATUS_LATE_ARRIVAL;

        return [
            'delay_minutes' => $late,
            'early_arrival_minutes' => 0,
            'status' => $status,
        ];
    }
}