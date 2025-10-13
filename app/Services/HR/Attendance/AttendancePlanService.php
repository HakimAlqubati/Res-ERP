<?php

namespace App\Services\HR\Attendance;

use App\Models\WorkPeriod;
use Carbon\Carbon;

class AttendancePlanService
{
    public function buildPlan(int $workPeriodId, string $fromDate, string $toDate): array
    {
        $period = WorkPeriod::findOrFail($workPeriodId);

        $startAt = $period->start_at;
        $endAt   = $period->end_at;

        $from = Carbon::parse($fromDate);
        $to   = Carbon::parse($toDate);

        $plan = [];

        while ($from->lte($to)) {
            // تحويل وقت الأساس إلى Carbon
            $checkIn  = $from->copy()->setTimeFromTimeString($startAt);
            $checkOut = $from->copy()->setTimeFromTimeString($endAt);

            // توليد انحراف عشوائي (من -120 إلى +120 دقيقة)
            $randomInOffset  = rand(-30, 30);
            $randomOutOffset = rand(-30, 30);

            // تطبيق الانحراف
            $checkIn->addMinutes($randomInOffset);
            $checkOut->addMinutes($randomOutOffset);

            // التأكد أن الخروج بعد الدخول
            if ($checkOut->lte($checkIn)) {
                $checkOut = $checkIn->copy()->addHours(8); // افتراضي 8 ساعات دوام
            }

            $plan[] = [
                'date'         => $from->toDateString(),
                'check_in'     => $checkIn->format('Y-m-d H:i:s'),
                'check_out'    => $checkOut->format('Y-m-d H:i:s'),
                'offset_in'    => $randomInOffset,
                'offset_out'   => $randomOutOffset,
            ];

            $from->addDay();
        }

        return $plan;
    }
}
