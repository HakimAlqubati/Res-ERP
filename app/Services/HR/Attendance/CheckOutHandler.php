<?php
namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class CheckOutHandler
{
    public function handle(array $attendanceData, $nearestPeriod, int $employeeId, string $date, Carbon $checkTime, $previousRecord = null): array
    {
        $endTime   = Carbon::parse($date . ' ' . $nearestPeriod->end_at);
        $startTime = Carbon::parse($date . ' ' . $nearestPeriod->start_at);

        // 1. تحديد سجل الدخول
        $checkinRecord = Attendance::where('employee_id', $employeeId)
            ->where('period_id', $nearestPeriod->id)
            ->where('accepted', 1)
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->whereDate('check_date', $date)
            ->latest('id')
            ->first();

        if ($checkinRecord) {
            $checkinTime       = Carbon::parse($checkinRecord->check_date . ' ' . $checkinRecord->check_time);
            $previousCheckDate = $date;
            $previousCheckId   = $checkinRecord->id;
            $previousDayName   = Carbon::parse($checkinRecord->check_date)->format('l');
        } elseif ($previousRecord) {
            $previousCheckDate                      = $previousRecord['in_previous']->check_date;
            $previousDayName                        = $previousRecord['previous_day_name'];
            $checkinTime                            = Carbon::parse($previousCheckDate . ' ' . $previousRecord['in_previous']->check_time);
            $previousCheckId                        = $previousRecord['in_previous']->id;
            $attendanceData['is_from_previous_day'] = 1;
            $attendanceData['check_date']           = $previousCheckDate;
            $attendanceData['day']                  = $previousDayName;
        } else {
            return $attendanceData;
        }

        // 2. احتساب المدة الفعلية

        if ($checkTime->lt($checkinTime)) {
            $checkTime = $checkTime->addDay();
        }

        $actualMinutes = $checkinTime->diffInMinutes($checkTime);
        $hoursActual   = floor($actualMinutes / 60);
        $minutesActual = $actualMinutes % 60;

        $currentDurationFormatted = sprintf('%02d:%02d', $hoursActual, $minutesActual);
        $actualDurationFormatted  = sprintf('%02d:%02d', floor($actualMinutes / 60), $actualMinutes % 60);

        $attendanceData['actual_duration_hourly']   = $actualDurationFormatted;
        $attendanceData['checkinrecord_id']         = $previousCheckId ?? null;
        $attendanceData['supposed_duration_hourly'] = $nearestPeriod?->supposed_duration;

        // 3. جمع المدد السابقة (total actual duration)
        $checkDateToSum    = $attendanceData['check_date'] ?? $date;
        $existingDurations = Attendance::where('employee_id', $employeeId)
            ->where('period_id', $nearestPeriod->id)
            ->where('accepted', 1)
            ->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
            ->whereDate('check_date', $checkDateToSum)
            ->pluck('actual_duration_hourly')
            ->toArray();

        $totalMinutes = $actualMinutes;
        foreach ($existingDurations as $duration) {
            if ($duration) {
                [$h, $m] = explode(':', $duration);
                $totalMinutes += ((int) $h * 60 + (int) $m);
            }
        }

        $attendanceData['total_actual_duration_hourly'] = sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);

        // 4. تحديد الحالة: تأخر أو خروج مبكر
        if ($checkTime->gt($endTime)) {
            $attendanceData['late_departure_minutes']  = $endTime->diffInMinutes($checkTime);
            $attendanceData['early_departure_minutes'] = 0;
            $attendanceData['status']                  = Attendance::STATUS_LATE_DEPARTURE;
        } elseif ($checkTime->lt($endTime)) {
            $attendanceData['late_departure_minutes']  = 0;
            $attendanceData['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
            $attendanceData['status']                  = Attendance::STATUS_EARLY_DEPARTURE;
        } else {
            $attendanceData['late_departure_minutes']  = 0;
            $attendanceData['early_departure_minutes'] = 0;
            $attendanceData['status']                  = Attendance::STATUS_ON_TIME;
        }

        // 5. التعامل مع الحقول الأخرى
        $attendanceData['delay_minutes'] = 0;

        // 6. حالات إضافية
        $allowedTimeAfter = Carbon::parse($nearestPeriod->end_at)->addHours((int) Setting::getSetting('hours_count_after_period_after'));
        if (
            $nearestPeriod->end_at > $allowedTimeAfter->format('H:i:s') &&
            $checkTime->toTimeString() < $nearestPeriod->end_at &&
            $allowedTimeAfter->format('H:i:s') > $checkTime->toTimeString()
        ) {
            $nearestPeriodEnd                          = Carbon::parse($nearestPeriod->end_at)->subDay();
            $attendanceData['status']                  = Attendance::STATUS_LATE_DEPARTURE;
            $attendanceData['delay_minutes']           = $nearestPeriodEnd->diffInMinutes($checkTime);
            $attendanceData['early_arrival_minutes']   = 0;
            $attendanceData['early_departure_minutes'] = 0;
        }

        if ($nearestPeriod->day_and_night) {
            if ($checkTime->toTimeString() > $nearestPeriod->start_at && $checkTime->toTimeString() <= '23:59:59') {
                $endTime                                   = $endTime->addDay();
                $attendanceData['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
                $attendanceData['status']                  = Attendance::STATUS_EARLY_DEPARTURE;
            }
        }
        if (is_array($attendanceData) && isset($attendanceData['employee_id'], $attendanceData['period_id'])) {

            return [
                'success' => true,
                'data'    => $attendanceData,
            ];

        }
        return $attendanceData;
    }
}