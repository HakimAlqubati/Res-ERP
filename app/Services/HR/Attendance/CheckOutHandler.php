<?php

namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class CheckOutHandler
{
    public function handle(array $attendanceData, $nearestPeriod, int $employeeId, string $date, Carbon $checkTime, $previousRecord = null): array
    {

        // 1. تحديد سجل الدخول
        $checkinRecord = Attendance::where('employee_id', $employeeId)
            ->where('period_id', $nearestPeriod->id)
            ->where('accepted', 1)
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->whereDate('check_date', $date)
            ->latest('id')
            ->first();


        // // ✅ استخدم bounds إن كانت معلّقة على الفترة
        $bounds = (method_exists($nearestPeriod, 'relationLoaded') && $nearestPeriod->relationLoaded('bounds'))
            ? $nearestPeriod->getRelation('bounds')
            : null;


        $windowStart = $bounds['windowStart']->format('H:i:s');
        $windowEnd = $bounds['windowEnd']->format('H:i:s');


        // dd($windowStart,$windowEnd,$checkTime->format('H:i:s'),$checkinRecord);
        $endTimeFromBounds = $bounds['periodEnd']->format('Y-m-d H:i:s');

        // dd($endTimeFromBounds, $date);
        $dateModified = $date;
        if (
            $checkTime->format('H:i:s') >= '00:00:00' &&
            $checkTime->format('H:i:s') <= $windowEnd &&
            $checkinRecord &&
            $checkinRecord->period->day_and_night
        ) {
            $dateModified = Carbon::parse($date)->addDay()->toDateString();
        }
        // dd($dateModified);
        // dd($date);
        $endTime   = Carbon::parse($dateModified . ' ' . $nearestPeriod->end_at);
        $startTime = Carbon::parse($date . ' ' . $nearestPeriod->start_at);

        $checkTime = $bounds['currentTimeObj'];
        // dd($checkTime,$bounds['currentTimeObj']);
        $realCheckDate = $attendanceData['real_check_date'] ?? $date;

        $allowedTimeAfter = Carbon::parse($nearestPeriod->end_at)->addHours((int) Setting::getSetting('hours_count_after_period_after'));

        // dd($allowedTimeAfter);



        // dd($checkinRecord,$previousRecord,$realCheckDate);
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
            // تفريغ الحقول الزمنية لعدم توفر Check-in
            $attendanceData['actual_duration_hourly']       = $attendanceData['actual_duration_hourly'] ?? '00:00';
            $attendanceData['total_actual_duration_hourly'] = $attendanceData['total_actual_duration_hourly'] ?? '00:00';
            $attendanceData['supposed_duration_hourly']     = $nearestPeriod?->supposed_duration;
            $attendanceData['checkinrecord_id']             = null;
            $attendanceData['delay_minutes']                = 0;

            // $checkTime تم تعيينه مسبقًا إلى $bounds['currentTimeObj']
            // $endTime محسوب مسبقًا حسب start/end والفترة الليلية
            if ($nearestPeriod->day_and_night) {
                if ($checkTime->equalTo($endTime)) {
                    $attendanceData['status'] = Attendance::STATUS_ON_TIME;
                    $attendanceData['late_departure_minutes']  = 0;
                    $attendanceData['early_departure_minutes'] = 0;
                } elseif ($checkTime->greaterThan($endTime)) {
                    $attendanceData['status'] = Attendance::STATUS_LATE_DEPARTURE;
                    $attendanceData['late_departure_minutes']  = $endTime->diffInMinutes($checkTime);
                    $attendanceData['early_departure_minutes'] = 0;
                } else {
                    $attendanceData['status'] = Attendance::STATUS_EARLY_DEPARTURE;
                    $attendanceData['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
                    $attendanceData['late_departure_minutes']  = 0;
                }
            } else {
                if ($checkTime->gt($endTime)) {
                    $attendanceData['status'] = Attendance::STATUS_LATE_DEPARTURE;
                    $attendanceData['late_departure_minutes']  = $endTime->diffInMinutes($checkTime);
                    $attendanceData['early_departure_minutes'] = 0;
                } elseif ($checkTime->lt($endTime)) {
                    $attendanceData['status'] = Attendance::STATUS_EARLY_DEPARTURE;
                    $attendanceData['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
                    $attendanceData['late_departure_minutes']  = 0;
                } else {
                    $attendanceData['status'] = Attendance::STATUS_ON_TIME;
                    $attendanceData['late_departure_minutes']  = 0;
                    $attendanceData['early_departure_minutes'] = 0;
                }
            }
            return $attendanceData;
        }

        // 2. احتساب المدة الفعلية

        if ($checkTime->lt($checkinTime) && $checkinRecord->period->start_at !== '00:00:00') {
            $checkTime = $checkTime->addDay();
        }

        if ($checkinRecord->period->start_at == '00:00:00') {

            $checkinTime = Carbon::parse($checkinRecord->real_check_date . ' ' . $checkinRecord->check_time);
        }


        // معالجة حالة الانصراف بعد منتصف الليل
        // if ($checkTime->lt($checkinTime)) {
        if (
            $checkinRecord->period->day_and_night
            && $checkTime->toTimeString() >= '00:00:00'
            && $checkTime->toTimeString() <= $allowedTimeAfter->format('H:i:s')
            && $checkinTime->toTimeString() >= '00:00:00'
            && $checkinTime->toTimeString() <= $allowedTimeAfter->format('H:i:s')
        ) {
            $checkinTime = $checkinTime->copy()->addDay();
        }
        // dd($checkinTime, $checkTime);
        $actualMinutes = $checkinTime->diffInMinutes($checkTime);
        // dd($actualMinutes, $checkinTime, $checkTime);
        $hoursActual   = floor($actualMinutes / 60);
        $minutesActual = $actualMinutes % 60;

        $currentDurationFormatted = sprintf('%02d:%02d', $hoursActual, $minutesActual);
        $actualDurationFormatted  = sprintf('%02d:%02d', floor($actualMinutes / 60), $actualMinutes % 60);

        // dd($actualDurationFormatted);
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
        // dd($actualMinutes);
        // dd($existingDurations);
        $totalMinutes = $actualMinutes;
        foreach ($existingDurations as $duration) {
            if ($duration) {
                [$h, $m] = explode(':', $duration);
                $totalMinutes += ((int) $h * 60 + (int) $m);
            }
        }
        // dd($totalMinutes);

        $attendanceData['total_actual_duration_hourly'] = sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
        // dd(
        //     $attendanceData,
        //     $totalMinutes,
        //     $checkTime->gt($endTimeFromBounds),
        //     $checkTime,
        //     $endTimeFromBounds
        // );
        // dd($checkTime, $endTime, $attendanceData);
        // dd($checkinTime);
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

        // dd($attendanceData, $checkTime, $endTimeFromBounds, $endTime);
        // 5. التعامل مع الحقول الأخرى
        $attendanceData['delay_minutes'] = 0;

        // 6. حالات إضافية
        // if (
        //     $nearestPeriod->end_at > $allowedTimeAfter->format('H:i:s') &&
        //     $checkTime->toTimeString() < $nearestPeriod->end_at &&
        //     $allowedTimeAfter->format('H:i:s') > $checkTime->toTimeString()
        // ) {
        //     $nearestPeriodEnd                          = Carbon::parse($nearestPeriod->end_at)->subDay();
        //     $attendanceData['status']                  = Attendance::STATUS_LATE_DEPARTURE;
        //     $attendanceData['early_arrival_minutes']   = 0;
        //     $attendanceData['early_departure_minutes'] = 0;
        // }

        if ($nearestPeriod->day_and_night) {
            if ($checkTime->toTimeString() > $nearestPeriod->start_at && $checkTime->toTimeString() <= '23:59:59') {
                $endTime                                   = $endTime->addDay();
                $attendanceData['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
                // $attendanceData['status']                  = Attendance::STATUS_EARLY_DEPARTURE;
            }
        }

        // === احسب STATUS باستخدام currentTimeObj ===
        $currentTimeObj = $bounds['currentTimeObj']; // fallback لو ما فيه bounds
        // $endForCompare  = $bounds['periodEnd'];
        $endForCompare  = $endTimeFromBounds;
        if (! $currentTimeObj instanceof Carbon) {
            $currentTimeObj = Carbon::parse($currentTimeObj);
        }
        if (! $endForCompare instanceof Carbon) {
            $endForCompare = Carbon::parse($endForCompare);
        }

        // صفّر الدقائق أولاً 

        // dd($currentTimeObj, $date, $endTime, $endForCompare);
        if ($nearestPeriod->day_and_night) {
            if ($currentTimeObj->equalTo($endTime)) {
                $attendanceData['status'] = Attendance::STATUS_ON_TIME;
            } elseif ($currentTimeObj->greaterThan($endTime)) {
                // خرج بعد نهاية الفترة
                $attendanceData['status']                 = Attendance::STATUS_LATE_DEPARTURE;
                $attendanceData['late_departure_minutes'] = $endTime->diffInMinutes($currentTimeObj);
            } else {
                // خرج قبل نهاية الفترة
                $attendanceData['status']                    = Attendance::STATUS_EARLY_DEPARTURE;
                $attendanceData['early_departure_minutes']   = $currentTimeObj->diffInMinutes($endTime);
            }
        }
        // dd($attendanceData);
        if (is_array($attendanceData) && isset($attendanceData['employee_id'], $attendanceData['period_id'])) {

            return [
                'success' => true,
                'type_required' => false,
                'data'    => $attendanceData,
            ];
        }
        return $attendanceData;
    }
}
