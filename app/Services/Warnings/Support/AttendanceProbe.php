<?php

namespace App\Services\Warnings\Support;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class AttendanceProbe
{
    public function hasCheckinBefore(Employee $emp, int $periodId, Carbon $deadline): bool
    {
        $date = $deadline->toDateString(); // "2025-12-09"

        return $emp->attendances()
            ->where('accepted', 1)
            ->where('period_id', $periodId)
            ->where('check_type', 'checkin')
            // فلترة بتاريخ اليوم فقط
            ->whereRaw(
                "COALESCE(real_check_date, check_date) = ?",
                [$date]
            )
            // ثم التحقق من أن الوقت قبل الـ deadline
            ->whereRaw(
                "STR_TO_DATE(CONCAT(COALESCE(real_check_date, check_date),' ', check_time), '%Y-%m-%d %H:%i:%s') < ?",
                [$deadline->format('Y-m-d H:i:s')]
            )
            ->exists();
    }
}
