<?php

namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class CheckOutHandlerNew
{
    public function handle(
        array $attendanceData,
        $nearestPeriod,
        int $employeeId,
        string $date,
        Carbon $checkTime,
        $previousRecord = null
    ): array {
        // 1. جلب سجل الدخول أو الرجوع لسجل سابق
        [$checkinRecord, $checkinTime, $previousCheckId, $previousCheckDate, $previousDayName] =
            $this->resolveCheckinRecord($nearestPeriod, $employeeId, $date, $previousRecord, $attendanceData);

        if (!$checkinRecord && !$previousRecord) {
            return $attendanceData; // لا يوجد سجل دخول
        }

        // 2. إعداد النوافذ الزمنية
        $bounds            = $this->getBounds($nearestPeriod);
        $windowEnd         = $bounds['windowEnd'] ?? null;
        $endTimeFromBounds = $bounds['periodEnd'] ?? null;
        $currentTimeObj    = $bounds['currentTimeObj'] ?? $checkTime;

        // 3. تعديل التاريخ في حال العمل يوم/ليل
        $dateModified = $this->adjustDateForDayAndNight($date, $checkTime, $windowEnd, $checkinRecord);

        $endTime   = Carbon::parse($dateModified . ' ' . $nearestPeriod->end_at);
        $startTime = Carbon::parse($date . ' ' . $nearestPeriod->start_at);

        // 4. تعديل وقت الدخول والخروج حسب اليوم/الليل
        [$checkinTime, $checkTime] = $this->normalizeCheckTimes($checkinRecord, $checkinTime, $checkTime, $nearestPeriod);

        // 5. حساب المدة الفعلية
        $actualMinutes = $checkinTime->diffInMinutes($checkTime);
        $attendanceData = $this->applyActualDuration($attendanceData, $nearestPeriod, $previousCheckId, $date, $actualMinutes);

        // 6. تحديد الحالة: متأخر / مبكر / على الوقت
        $attendanceData = $this->determineStatus($attendanceData, $nearestPeriod, $checkTime, $endTime, $currentTimeObj);

        // 7. إرجاع النتيجة
        if (isset($attendanceData['employee_id'], $attendanceData['period_id'])) {
            return [
                'success' => true,
                'data'    => $attendanceData,
            ];
        }

        return $attendanceData;
    }

    // ====== دوال مساعدة ======

    private function resolveCheckinRecord($nearestPeriod, int $employeeId, string $date, $previousRecord, array &$attendanceData): array
    {
        $checkinRecord = Attendance::where('employee_id', $employeeId)
            ->where('period_id', $nearestPeriod->id)
            ->where('accepted', 1)
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->whereDate('check_date', $date)
            ->latest('id')
            ->first();

        if ($checkinRecord) {
            return [
                $checkinRecord,
                Carbon::parse($checkinRecord->check_date . ' ' . $checkinRecord->check_time),
                $checkinRecord->id,
                $date,
                Carbon::parse($checkinRecord->check_date)->format('l'),
            ];
        }

        if ($previousRecord) {
            $previousCheckDate = $previousRecord['in_previous']->check_date;
            $attendanceData['is_from_previous_day'] = 1;
            $attendanceData['check_date']           = $previousCheckDate;
            $attendanceData['day']                  = $previousRecord['previous_day_name'];

            return [
                null,
                Carbon::parse($previousCheckDate . ' ' . $previousRecord['in_previous']->check_time),
                $previousRecord['in_previous']->id,
                $previousCheckDate,
                $previousRecord['previous_day_name'],
            ];
        }

        return [null, null, null, null, null];
    }

    private function getBounds($nearestPeriod): array
    {
        if (method_exists($nearestPeriod, 'relationLoaded') && $nearestPeriod->relationLoaded('bounds')) {
            return $nearestPeriod->getRelation('bounds');
        }
        return [];
    }

    private function adjustDateForDayAndNight(string $date, Carbon $checkTime, ?string $windowEnd, $checkinRecord): string
    {
        if (
            $windowEnd &&
            $checkTime->format('H:i:s') >= '00:00:00' &&
            $checkTime->format('H:i:s') <= $windowEnd &&
            $checkinRecord?->period?->day_and_night
        ) {
            return Carbon::parse($date)->addDay()->toDateString();
        }
        return $date;
    }

    private function normalizeCheckTimes($checkinRecord, $checkinTime, $checkTime, $nearestPeriod): array
    {
        $allowedTimeAfter = Carbon::parse($nearestPeriod->end_at)
            ->addHours((int) Setting::getSetting('hours_count_after_period_after'));

        if ($checkTime->lt($checkinTime) && $checkinRecord?->period?->start_at !== '00:00:00') {
            $checkTime = $checkTime->addDay();
        }

        if ($checkinRecord?->period?->start_at === '00:00:00') {
            $checkinTime = Carbon::parse($checkinRecord->real_check_date . ' ' . $checkinRecord->check_time);
        }

        if (
            $checkinRecord?->period?->day_and_night &&
            $checkTime->toTimeString() >= '00:00:00' &&
            $checkTime->toTimeString() <= $allowedTimeAfter->format('H:i:s') &&
            $checkinTime->toTimeString() >= '00:00:00' &&
            $checkinTime->toTimeString() <= $allowedTimeAfter->format('H:i:s')
        ) {
            $checkinTime = $checkinTime->copy()->addDay();
        }

        return [$checkinTime, $checkTime];
    }

    private function applyActualDuration(array $attendanceData, $nearestPeriod, ?int $previousCheckId, string $date, int $actualMinutes): array
    {
        $attendanceData['actual_duration_hourly']   = sprintf('%02d:%02d', floor($actualMinutes / 60), $actualMinutes % 60);
        $attendanceData['checkinrecord_id']         = $previousCheckId ?? null;
        $attendanceData['supposed_duration_hourly'] = $nearestPeriod?->supposed_duration;

        // جمع المدد السابقة
        $checkDateToSum = $attendanceData['check_date'] ?? $date;
        $existingDurations = Attendance::where('employee_id', $attendanceData['employee_id'])
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

        return $attendanceData;
    }

    private function determineStatus(array $attendanceData, $nearestPeriod, Carbon $checkTime, Carbon $endTime, Carbon $currentTimeObj): array
    {
        $attendanceData['delay_minutes'] = 0;

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

        // لو الفترة Day & Night نعتمد على الـ currentTimeObj
        if ($nearestPeriod->day_and_night) {
            if ($currentTimeObj->equalTo($endTime)) {
                $attendanceData['status'] = Attendance::STATUS_ON_TIME;
            } elseif ($currentTimeObj->greaterThan($endTime)) {
                $attendanceData['status']                 = Attendance::STATUS_LATE_DEPARTURE;
                $attendanceData['late_departure_minutes'] = $endTime->diffInMinutes($currentTimeObj);
            } else {
                $attendanceData['status']                  = Attendance::STATUS_EARLY_DEPARTURE;
                $attendanceData['early_departure_minutes'] = $currentTimeObj->diffInMinutes($endTime);
            }
        }

        return $attendanceData;
    }
}
