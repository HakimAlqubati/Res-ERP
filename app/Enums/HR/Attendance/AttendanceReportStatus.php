<?php
namespace App\Enums\HR\Attendance;

enum AttendanceReportStatus: string {
    case Absent                 = 'absent';
    case IncompleteCheckinOnly  = 'incomplete_checkin_only';
    case IncompleteCheckoutOnly = 'incomplete_checkout_only';
    case Present                = 'present';
    case Partial                = 'partial';
    case Leave                  = 'leave';
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
        };
    }
}