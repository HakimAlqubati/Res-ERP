<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * استثناء: لا توجد وردية للموظف في هذا الوقت
 */
class NoShiftFoundException extends AttendanceException
{
    protected string $errorKey = 'no_shift_found';

    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? __('notifications.you_dont_have_periods_today')
        );
    }
}
