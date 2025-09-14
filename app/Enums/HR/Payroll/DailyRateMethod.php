<?php

namespace App\Enums\HR\Payroll;

enum DailyRateMethod: string
{
    case By30Days      = 'by_30_days';
    case ByMonthDays   = 'by_month_days';
    case ByWorkingDays = 'by_working_days';

    public function label(): string
    {
        return match ($this) {
            self::By30Days      => 'Divide by 30 days',
            self::ByMonthDays   => 'Divide by number of days in the month',
            self::ByWorkingDays => 'Divide by actual working days',
        };
    }

    /**
     * Convert the enum to dropdown options
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
