<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * يُرمى عند محاولة تسجيل حضور في نفس الوقت بالضبط كآخر سجل
 * 
 * يمنع تسجيل دخول أو خروج في نفس اللحظة (نفس الثانية) التي تم فيها آخر تسجيل
 */
class DuplicateTimestampException extends AttendanceException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? __('notifications.duplicate_timestamp_not_allowed')
        );
    }
}
