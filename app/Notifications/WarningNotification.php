<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $detail,
        public string $level = 'warning', // warning | critical | info
        public ?array $context = null,    // معلومات إضافية: product_id, store_id, ...
        public ?string $link = null       // رابط للتنقّل السريع
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'title'   => $this->title,
            'detail'  => $this->detail,
            'level'   => $this->level,
            'context' => $this->context,
            'link'    => $this->link,
        ];
    }
}
