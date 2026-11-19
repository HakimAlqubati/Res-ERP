<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Contracts;

use Carbon\CarbonImmutable;

interface HijriDateConverterInterface
{
    /**
     * Convert a Hijri date (year, month, day) into an expected Gregorian date.
     */
    public function hijriToGregorian(int $hijriYear, int $hijriMonth, int $hijriDay): CarbonImmutable;

    /**
     * Convert a Gregorian date into its corresponding Hijri [year, month, day].
     *
     * @return array{year: int, month: int, day: int}
     */
    public function gregorianToHijri(CarbonImmutable $date): array;

    /**
     * Get the Hijri years that overlap with the given Gregorian year.
     *
     * @return array<int, int>
     */
    public function overlappingHijriYears(int $gregorianYear): array;
}
