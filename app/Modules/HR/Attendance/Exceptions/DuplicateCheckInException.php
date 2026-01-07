<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * استثناء: الموظف مسجل دخول بالفعل
 */
class DuplicateCheckInException extends AttendanceException
{
    protected string $errorKey = 'duplicate_checkin';

    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? __('notifications.you_are_already_checked_in')
        );
    }
}
