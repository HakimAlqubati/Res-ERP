<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * Thrown when an auto-generated missed check-out request already exists
 * for the same employee on the same date, preventing duplicate submissions.
 */
class DuplicateMissedCheckoutRequestException extends AttendanceException
{
    protected string $errorKey = 'duplicate_missed_checkout_request';

    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? __('notifications.duplicate_missed_checkout_request')
        );
    }
}
