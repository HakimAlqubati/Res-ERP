<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Rules;

use App\Models\HolidayTemplate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidHolidayTemplateDayRule implements ValidationRule
{
    public function __construct(
        private readonly ?string $type,
        private readonly ?int $month,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->month === null || $value === null || $value === '') {
            return;
        }

        $day = (int) $value;

        if (HolidayTemplate::isValidDayForMonth($this->type, $this->month, $day)) {
            return;
        }

        $maxDay = HolidayTemplate::maxDayForMonth($this->type, $this->month);
        $monthLabel = HolidayTemplate::monthOptions($this->type)[$this->month] ?? (string) $this->month;

        $fail("Day {$day} is not valid for {$monthLabel}. Maximum allowed day is {$maxDay}.");
    }
}
