<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * استثناء: تسجيل الحضور بدون شيفت غير مُفعَّل في إعدادات النظام
 *
 * يُرمى عندما يحاول موظف تسجيل حضور بدون وردية مُعيَّنة
 * وإعداد shiftless_attendance_mode = deny
 */
class ShiftlessAttendanceDisabledException extends AttendanceException
{
    protected string $errorKey = 'shiftless_attendance_disabled';

    public function __construct()
    {
        parent::__construct(
            __('notifications.shiftless_attendance_disabled',
                ['default' => 'Attendance without an assigned shift is not allowed.'])
        );
    }
}
