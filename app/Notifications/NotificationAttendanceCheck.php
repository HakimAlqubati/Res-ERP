<?php
 
namespace App\Notifications;
 
use Filament\Livewire\Notifications;
use Filament\Notifications\Notification as BaseNotification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;

class NotificationAttendanceCheck extends BaseNotification
{
    protected string $size = 'md';
 
    public static Alignment $alignment = Alignment::Center;

    
    public static VerticalAlignment $verticalAlignment = VerticalAlignment::Start;

    public static function alignment(Alignment $alignment): void
    {
        static::$alignment = $alignment;
    }
    
    public static function verticalAlignment(VerticalAlignment $alignment): void
    {
        static::$verticalAlignment = $alignment;
    }

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