<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string|array $detail,
        public string $level = 'warning', // warning | critical | info
        public ?array $context = null,    // product_id, store_id, ...
        public ?string $link = null,
        public ?string $scopeKey = null,  // مفتاح لمنع التكرار
        public ?string $expiresAt = null, // تاريخ انتهاء بصيغة ISO8601
        public string $status = 'active'  // active | resolved
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'      => $this->title,
            'detail'     => $this->detail,
            'level'      => $this->level,
            'context'    => $this->context,
            'link'       => $this->link,
            'scope_key'  => $this->scopeKey,
            'expires_at' => $this->expiresAt,
            'status'     => $this->status,
        ];
    }

    // لو حبيت تمنع تكرار نفس التحذير:
    public function id(): string
    {
        return 'warn:' . sha1(($this->scopeKey ?? $this->title) . '|' . $this->level);
    }
}
