<?php

namespace App\Modules\HR\Attendance\Enums;

/**
 * طرق تسجيل الحضور (RFID، كاميرا، طلب)
 */
enum AttendanceType: string
{
    case WEBCAM = 'webcam';
    case RFID = 'rfid';
    case REQUEST = 'request';

    /**
     * الحصول على التسمية المترجمة
     */
    public function label(): string
    {
        return match ($this) {
            self::WEBCAM => __('Webcam'),
            self::RFID => __('RFID'),
            self::REQUEST => __('Request'),
        };
    }

    /**
     * الحصول على الأيقونة المناسبة
     */
    public function icon(): string
    {
        return match ($this) {
            self::WEBCAM => 'heroicon-o-camera',
            self::RFID => 'heroicon-o-credit-card',
            self::REQUEST => 'heroicon-o-document-text',
        };
    }

    /**
     * الحصول على جميع الخيارات كمصفوفة
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * القيمة الافتراضية
     */
    public static function default(): self
    {
        return self::RFID;
    }
}
