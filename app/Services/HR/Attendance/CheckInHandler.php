<?php

namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class CheckInHandler
{
    public $status = '';
    public int $delayMinutes = 0;           // ✅ جديد
    public int $earlyArrivalMinutes = 0;
    public function handle(array $attendanceData, $nearestPeriod, Carbon $checkTime, string $checkTimeStr, string $day, $previousRecord = null, $type = ''): array | string
    {
        // // ✅ استخدم bounds إن كانت معلّقة على الفترة
        $bounds = (method_exists($nearestPeriod, 'relationLoaded') && $nearestPeriod->relationLoaded('bounds'))
            ? $nearestPeriod->getRelation('bounds')
            : null;

        $currentTimeBound = $bounds['currentTimeObj'] ?? null;
        $periodStartTimeBound = $bounds['periodStart'] ?? null;
        $periodEndTimeBound = $bounds['periodEnd'] ?? null;

        // dd($currentTimeBound);
        // ✅ اضبط status + الدقائق بالاعتماد على bounds (إن وُجدت)
        if ($currentTimeBound && $periodStartTimeBound) {
            $ct = $currentTimeBound instanceof Carbon ? $currentTimeBound : Carbon::parse($currentTimeBound);
            $ps = $periodStartTimeBound instanceof Carbon ? $periodStartTimeBound : Carbon::parse($periodStartTimeBound);

            if ($ct->equalTo($ps)) {
                $attendanceData['status'] = Attendance::STATUS_ON_TIME;
                $this->delayMinutes = 0;
                $this->earlyArrivalMinutes = 0;
            } elseif ($ct->lessThan($ps)) {
                // حضور مبكر
                $attendanceData['status'] = Attendance::STATUS_EARLY_ARRIVAL;
                $this->earlyArrivalMinutes = $ps->diffInMinutes($ct);
                $this->delayMinutes = 0;
            } else {
                // تأخر عن بداية الفترة
                $attendanceData['status'] = Attendance::STATUS_LATE_ARRIVAL;
                $this->delayMinutes = $ct->diffInMinutes($ps);
                $this->earlyArrivalMinutes = 0;
            }
        }
        $this->status = $attendanceData['status'] ?? $this->status;
        $this->delayMinutes = abs($this->delayMinutes);

        $date            = $attendanceData['check_date'];
        $employee        = $attendanceData['employee'];
        $realCheckDate   = $attendanceData['real_check_date'] ?? $date;
        $periodEndTime   = $nearestPeriod->end_at;
        $periodStartTime = $nearestPeriod->start_at;

        $diff2 = $this->calculateTimeDifferenceV3($checkTime->toTimeString(), $nearestPeriod, $date);
        $diff = $currentTimeBound->diffInHours($periodStartTimeBound);
        // $diff = $this->calculateTimeDifferenceV3($checkTime->toTimeString(), $nearestPeriod, $currentTimeBound->format('Y-m-d'));
        // dd($diff);
        // dd($currentTimeBound->format('Y-m-d'),$diff);
        if (! $this->isWithinAllowedBeforePeriod($nearestPeriod, $date, $checkTimeStr) && $periodStartTime !== '00:00:00') {
            $message = __('notifications.attendance_out_of_range_before_period');
            // Attendance::storeNotAccepted($employee, $date, $checkTimeStr, $day, $message, $nearestPeriod->id, $attendanceData['attendance_type']);
            return $message;
        }
 
        if (
            $currentTimeBound->lt($periodStartTimeBound) &&

            $diff > Setting::getSetting('hours_count_after_period_after') && $type === ''
        ) {
            // dd('sf',$checkTimeStr, $periodStartTime, $diff,$type);
            $message = __('notifications.you_cannot_attendance_before') . ' ' . $diff . ' ' . __('notifications.hours');
            // Attendance::storeNotAccepted($employee, $date, $checkTimeStr, $day, $message, $nearestPeriod->id, $attendanceData['attendance_type']);
            return $message;
        }

        // شفت ليلي (اليوم السابق)
        if (
            $periodStartTime > $periodEndTime &&
            $checkTimeStr >= '00:00:00' && $checkTimeStr < $periodEndTime &&
            is_null($previousRecord)
        ) {
            $prevDate                     = Carbon::parse($date)->subDay();
            $attendanceData['check_date'] = $prevDate->toDateString();
            $attendanceData['day']        = $prevDate->format('l');
        }

        // إن وُجد سجل سابق
        if ($previousRecord && isset($previousRecord['in_previous'])) {
            $attendanceData['is_from_previous_day'] = 1;
            $attendanceData['check_date']           = $previousRecord['in_previous']?->check_date;
        }

        $attendanceData = array_merge(
            $attendanceData,
            $this->storeCheckIn($nearestPeriod, $checkTime, $attendanceData['check_date'], $attendanceData['real_check_date'])
        );
        if (is_array($attendanceData) && isset($attendanceData['employee_id'], $attendanceData['period_id'])) {
            return [
                'success' => true,
                'data'    => $attendanceData,
            ];
        }

        return $attendanceData;
    }

    protected function isWithinAllowedBeforePeriod($period, $date, $time): bool
    {
        $allowedTime = Carbon::createFromFormat('H:i:s', $period->start_at)
            ->subHours((int) Setting::getSetting('hours_count_after_period_before'))
            ->format('H:i:s');

        $checkTime = Carbon::parse("$date $time");
        $allowed   = Carbon::parse("$date $allowedTime");

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

    protected function storeCheckIn($period, Carbon $checkTime, string $date, $realCheckDate): array
    {
        if ($period->start_at == '00:00:00') {
            $checkTime = Carbon::parse("$realCheckDate {$checkTime->toTimeString()} ");
        }
        $startTime  = Carbon::parse("$date {$period->start_at}");
        $earlyLimit = Setting::getSetting('early_attendance_minutes');

        if ($checkTime->lt($startTime)) {
            $early = $checkTime->diffInMinutes($startTime);
            return [
                'delay_minutes'         => $this->delayMinutes,
                'early_arrival_minutes' => $early,
                'status'                => $this->status,
                // 'status'                => $early >= $earlyLimit ? Attendance::STATUS_EARLY_ARRIVAL : Attendance::STATUS_ON_TIME,
            ];
        }

        $late   = $startTime->diffInMinutes($checkTime);
        // $status = $late <= $earlyLimit ? Attendance::STATUS_ON_TIME : Attendance::STATUS_LATE_ARRIVAL;

        return [
            'delay_minutes'         => $this->delayMinutes,
            'early_arrival_minutes' => 0,
            'status'                => $this->status,
        ];
    }
}
