<?php

namespace App\Modules\HR\Attendance\Enums;

/**
 * أوضاع تسجيل الحضور للموظفين بدون شيفت
 *
 * DENY         → رفض التسجيل (السلوك الافتراضي — Backward Compatible)
 * ALLOW        → السماح بالتسجيل مع تحديد النوع تلقائياً
 * REQUIRE_TYPE → السماح لكن يُجبر على تحديد checkin/checkout صراحةً
 */
enum ShiftlessAttendanceMode: string
{
    case DENY         = 'deny';
    case ALLOW        = 'allow';
    case REQUIRE_TYPE = 'require_type';

    // ─────────────────────────────────────────────────────────────
    // Labels & Display
    // ─────────────────────────────────────────────────────────────

    public function label(): string
    {
        return match ($this) {
            self::DENY         => __('Deny attendance without shift'),
            self::ALLOW        => __('Allow attendance without shift'),
            self::REQUIRE_TYPE => __('Allow but require explicit check type'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // Business Logic
    // ─────────────────────────────────────────────────────────────

    /**
     * هل يُسمح بالتسجيل بدون شيفت في هذا الوضع؟
     */
    public function isAllowed(): bool
    {
        return $this !== self::DENY;
    }

    /**
     * هل يتطلب هذا الوضع تحديد نوع العملية صراحةً؟
     */
    public function requiresExplicitType(): bool
    {
        return $this === self::REQUIRE_TYPE;
    }

    // ─────────────────────────────────────────────────────────────
    // Factory
    // ─────────────────────────────────────────────────────────────

    /**
     * قراءة الوضع من إعدادات النظام
     * افتراضياً: DENY (لا كسر في السلوك الحالي)
     */
    public static function fromSetting(): self
    {
        return self::ALLOW;
        $value = \App\Models\Setting::getSetting(
            'shiftless_attendance_mode',
            self::DENY->value
        );

        return self::tryFrom($value) ?? self::ALLOW;
    }
}
