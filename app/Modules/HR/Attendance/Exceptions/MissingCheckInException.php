<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * استثناء: محاولة تسجيل خروج بدون تسجيل دخول
 */
class MissingCheckInException extends AttendanceException
{
    protected string $errorKey = 'missing_checkin';

    public function __construct(?string $periodName = null)
    {
        $message = __('notifications.cannot_checkout_without_checkin');

        if ($periodName) {
            $message .= " (" . __('Period') . ": $periodName)";
        }

        parent::__construct($message);
    }
}
