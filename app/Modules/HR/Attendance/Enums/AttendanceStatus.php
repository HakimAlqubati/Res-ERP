<?php

namespace App\Modules\HR\Attendance\Enums;

/**
 * حالات الحضور (مبكر، متأخر، في الوقت، إلخ)
 */
enum AttendanceStatus: string
{
    case EARLY_ARRIVAL = 'early_arrival';
    case LATE_ARRIVAL = 'late_arrival';
    case ON_TIME = 'on_time';
    case EARLY_DEPARTURE = 'early_departure';
    case LATE_DEPARTURE = 'late_departure';

    /**
     * الحصول على التسمية المترجمة
     */
    public function label(): string
    {
        return match ($this) {
            self::EARLY_ARRIVAL => __('Early Arrival'),
            self::LATE_ARRIVAL => __('Late Arrival'),
            self::ON_TIME => __('On Time'),
            self::EARLY_DEPARTURE => __('Early Departure'),
            self::LATE_DEPARTURE => __('Late Departure'),
        };
    }

    /**
     * الحصول على اللون المناسب للحالة (لواجهة Filament)
     */
    public function color(): string
    {
        return match ($this) {
            self::EARLY_ARRIVAL => 'info',
            self::LATE_ARRIVAL => 'danger',
            self::ON_TIME => 'success',
            self::EARLY_DEPARTURE => 'warning',
            self::LATE_DEPARTURE => 'primary',
        };
    }

    /**
     * الحصول على الأيقونة المناسبة للحالة
     */
    public function icon(): string
    {
        return match ($this) {
            self::EARLY_ARRIVAL => 'heroicon-o-clock',
            self::LATE_ARRIVAL => 'heroicon-o-exclamation-triangle',
            self::ON_TIME => 'heroicon-o-check-circle',
            self::EARLY_DEPARTURE => 'heroicon-o-arrow-left-on-rectangle',
            self::LATE_DEPARTURE => 'heroicon-o-arrow-right-on-rectangle',
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
     * التحقق من أن الحالة تمثل تأخير
     */
    public function isLate(): bool
    {
        return $this === self::LATE_ARRIVAL;
    }

    /**
     * التحقق من أن الحالة تمثل حضور في الوقت
     */
    public function isOnTime(): bool
    {
        return $this === self::ON_TIME;
    }
}
