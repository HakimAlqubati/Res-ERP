<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * استثناء: الحضور مكتمل لهذا اليوم/الوردية
 */
class AttendanceCompletedException extends AttendanceException
{
    protected string $errorKey = 'attendance_completed';

    public function __construct(string $date, ?string $message = null)
    {
        parent::__construct(
            $message ?? __('notifications.attendance_already_completed_for_date', ['date' => $date])
        );
    }
}
