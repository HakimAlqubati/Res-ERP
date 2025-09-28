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
        // نجمع التاريخ/الوقت بطريقة تحترم real_check_date لو موجود
        return $emp->attendances()
            ->where('accepted', 1)
            ->where('period_id', $periodId)
            ->where('check_type', 'checkin')
            ->where(function($q) use ($deadline) {
                // أفضل مقارنة على datetime مركب
                $q->whereRaw(
                    "STR_TO_DATE(CONCAT(COALESCE(real_check_date, check_date),' ', check_time), '%Y-%m-%d %H:%i:%s') <= ?",
                    [$deadline->format('Y-m-d H:i:s')]
                );
            })
            ->exists();
    }
}
