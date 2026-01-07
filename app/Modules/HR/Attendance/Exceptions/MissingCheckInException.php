<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * استثناء: محاولة تسجيل خروج بدون تسجيل دخول
 */
class MissingCheckInException extends AttendanceException
{
    protected string $errorKey = 'missing_checkin';

    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? __('notifications.cannot_checkout_without_checkin')
        );
    }
}
