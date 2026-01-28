<?php

namespace App\Modules\HR\Attendance\Listeners;

use App\Modules\HR\Attendance\Events\AttendanceRejected;
use App\Modules\HR\Attendance\Events\CheckInRecorded;
use App\Modules\HR\Attendance\Events\CheckOutRecorded;
use Illuminate\Support\Facades\Log;

/**
 * مستمع تسجيل نشاطات الحضور
 * 
 * يسجل جميع أحداث الحضور في سجل النظام
 * للمتابعة والتدقيق
 */
class LogAttendanceActivity
{
    /**
     * معالجة حدث تسجيل الدخول
     */
    public function handleCheckIn(CheckInRecorded $event): void
    {
        Log::channel('daily')->info('Attendance: Check-in recorded', [
            'employee_id' => $event->employee->id,
            'employee_name' => $event->employee->name,
            'record_id' => $event->record->id,
            'check_time' => $event->record->check_time,
            'delay_minutes' => $event->delayMinutes,
            'status' => $event->status?->value,
            'is_late' => $event->isLate(),
        ]);
    }

    /**
     * معالجة حدث تسجيل الخروج
     */
    public function handleCheckOut(CheckOutRecorded $event): void
    {
        Log::channel('daily')->info('Attendance: Check-out recorded', [
            'employee_id' => $event->employee->id,
            'employee_name' => $event->employee->name,
            'record_id' => $event->record->id,
            'check_time' => $event->record->check_time,
            'actual_minutes' => $event->actualMinutes,
            'overtime_minutes' => $event->lateDepartureMinutes,
            'early_departure_minutes' => $event->earlyDepartureMinutes,
            'status' => $event->status?->value,
        ]);
    }

    /**
     * معالجة حدث رفض التسجيل
     */
    public function handleRejected(AttendanceRejected $event): void
    {
        Log::channel('daily')->warning('Attendance: Record rejected', [
            'employee_id' => $event->employee->id,
            'employee_name' => $event->employee->name,
            'reason' => $event->reason,
            'attempt_time' => $event->attemptTime->toDateTimeString(),
        ]);
    }
}
