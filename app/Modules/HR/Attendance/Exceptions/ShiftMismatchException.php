<?php

namespace App\Modules\HR\Attendance\Exceptions;

class ShiftMismatchException extends AttendanceException
{
    public function __construct()
    {
        parent::__construct('The selected shift is not valid for the current time or employee.');
    }
}
