<?php

namespace App\Modules\HR\Attendance\Enums;

/**
 * أنواع التحضير (دخول/خروج)
 */
enum CheckType: string
{
    case CHECKIN = 'checkin';
    case CHECKOUT = 'checkout';

    /**
     * الحصول على التسمية المترجمة
     */
    public function label(): string
    {
        return match ($this) {
            self::CHECKIN => __('Check in'),
            self::CHECKOUT => __('Checkout'),
        };
    }

    /**
     * الحصول على جميع الخيارات كمصفوفة
     */
    public static function options(): array
    {
        return [
            self::CHECKIN->value => self::CHECKIN->label(),
            self::CHECKOUT->value => self::CHECKOUT->label(),
        ];
    }

    /**
     * التحقق من أن القيمة هي تسجيل دخول
     */
    public function isCheckIn(): bool
    {
        return $this === self::CHECKIN;
    }

    /**
     * التحقق من أن القيمة هي تسجيل خروج
     */
    public function isCheckOut(): bool
    {
        return $this === self::CHECKOUT;
    }
}
