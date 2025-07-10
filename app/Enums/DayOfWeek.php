<?php
namespace App\Enums;

enum DayOfWeek: string {
    case Sunday    = 'sun';
    case Monday    = 'mon';
    case Tuesday   = 'tue';
    case Wednesday = 'wed';
    case Thursday  = 'thu';
    case Friday    = 'fri';
    case Saturday  = 'sat';

    public function arabic(): string
    {
        return match ($this) {
            self::Sunday => 'الأحد',
            self::Monday => 'الاثنين',
            self::Tuesday => 'الثلاثاء',
            self::Wednesday => 'الأربعاء',
            self::Thursday => 'الخميس',
            self::Friday => 'الجمعة',
            self::Saturday => 'السبت',
        };
    }

    public function english(): string
    {
        return match ($this) {
            self::Sunday => 'Sunday',
            self::Monday => 'Monday',
            self::Tuesday => 'Tuesday',
            self::Wednesday => 'Wednesday',
            self::Thursday => 'Thursday',
            self::Friday => 'Friday',
            self::Saturday => 'Saturday',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->english()])
            ->toArray();
    }

}