<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AttendanceNotification extends Notification
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast']; // تخزين DB + بث
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->message,
            'type' => self::class, // النوع يظهر في DB
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => $this->message,
            'type' => self::class,
        ]);
    }
}
