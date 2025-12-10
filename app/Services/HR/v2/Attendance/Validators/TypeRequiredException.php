<?php

namespace App\Services\HR\v2\Attendance\Validators;

use Exception;

/**
 * Exception thrown when a request near shift end requires explicit type selection.
 * This signals to the API client that user must choose between checkin/checkout.
 */
class TypeRequiredException extends Exception
{
    public function __construct(string $message = 'Type selection required')
    {
        parent::__construct($message);
    }
}
