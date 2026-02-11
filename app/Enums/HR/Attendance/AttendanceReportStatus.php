<?php

namespace App\Enums\HR\Attendance;

enum AttendanceReportStatus: string
{
    case Absent                 = 'absent';
    case IncompleteCheckinOnly  = 'incomplete_checkin_only';
    case IncompleteCheckoutOnly = 'incomplete_checkout_only';
    case Present                = 'present_days';
    case Partial                = 'partial';
    case Leave                  = 'leave';
    case WeeklyLeave            = 'weekly_leave'; // إجازة أسبوعية تلقائية
    case Future                 = 'future';
    case NoPeriods              = 'no_periods';

    public function label(): string
    {
        return match ($this) {
            self::Absent => 'Absent',
            self::IncompleteCheckinOnly => 'Check-in Only',
            self::IncompleteCheckoutOnly => 'Check-out Only',
            self::Present => 'Present',
            self::Partial => 'Parital',
            self::Leave => 'Leave',
            self::WeeklyLeave => 'Weekly Leave',
            self::Future => 'Future',
            self::NoPeriods => 'No Periods',
        };
    }

    // يمكنك تغيير الألوان حسب إطار العمل أو ذوقك (Bootstrap/Tailwind أو Hex code)
    public function color(): string
    {
        return match ($this) {
            self::Absent                 => 'danger',    // Bootstrap: أحمر. Tailwind: 'red-600'
            self::IncompleteCheckinOnly  => 'warning',   // Bootstrap: أصفر. Tailwind: 'yellow-500'
            self::IncompleteCheckoutOnly => 'warning',   // Bootstrap: أصفر. Tailwind: 'yellow-500'
            self::Present                => 'success',   // Bootstrap: أخضر. Tailwind: 'green-600'
            self::Partial                => 'info',      // Bootstrap: أزرق فاتح. Tailwind: 'sky-500'
            self::Leave                  => 'primary',   // Bootstrap: أزرق. Tailwind: 'blue-600'
            self::WeeklyLeave            => 'info',      // Bootstrap: أزرق فاتح. Tailwind: 'cyan-500'
            self::Future                 => 'secondary', // Bootstrap: رمادي. Tailwind: 'gray-400'
            self::NoPeriods              => 'secondary', // Bootstrap: رمادي. Tailwind: 'gray-400'
        };
    }

    // لو تريد Hex:
    public function hexColor(): string
    {
        return match ($this) {
            self::Absent                 => '#dc3545', // أحمر
            self::IncompleteCheckinOnly  => '#ffc107', // أصفر
            self::IncompleteCheckoutOnly => '#ffc107', // أصفر
            self::Present                => '#28a745', // أخضر
            self::Partial                => '#17a2b8', // أزرق سماوي
            self::Leave                  => '#007bff', // أزرق
            self::WeeklyLeave            => '#06b6d4', // سماوي (Cyan)
            self::Future                 => '#6c757d', // رمادي
            self::NoPeriods              => '#6c757d', // رمادي
        };
    }
}
