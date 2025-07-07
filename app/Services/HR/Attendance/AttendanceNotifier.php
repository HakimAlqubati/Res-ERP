<?php

namespace App\Services\HR\Attendance;

use Filament\Notifications\Notification;

class AttendanceNotifier
{
    public function success(string $message)
    {
        return Notification::make()
            ->title(__('notifications.success'))
            ->body($message)
            ->success()
            ->send();
    }

    public function warning(string $message)
    {
        return Notification::make()
            ->title(__('notifications.notify'))
            ->body($message)
            ->warning()
            ->send();
    }
}