<?php
 
namespace App\Notifications;
 
use Filament\Notifications\Notification as BaseNotification;
use Filament\Support\Enums\Alignment;

class NotificationAttendance extends BaseNotification
{
    protected string $size = 'md';
 
    public static Alignment $alignment = Alignment::Center;
    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'size' => $this->getSize(),
        ];
    }
    public static function fromArray(array $data): static
    {
        return parent::fromArray($data)->size($data['size']);
    }
 
    public function size(string $size): static
    {
        $this->size = $size;
 
        return $this;
    }


 
    public function getSize(): string
    {
        return $this->size;
    }
}