<?php
namespace App\Services\HR\Attendance;

class AttendanceNotifier
{
    public function success(string $message)
    {
        return $message;
    }

    public function warning(string $message)
    {
        return $message;
    }
}