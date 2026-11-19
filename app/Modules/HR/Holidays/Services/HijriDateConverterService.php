<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Services;

use App\Modules\HR\Holidays\Contracts\HijriDateConverterInterface;
use Carbon\CarbonImmutable;
use IntlCalendar;
use IntlTimeZone;

/**
 * Converts between Hijri (Umm al-Qura / Tabular fallback) and Gregorian dates
 * to produce astronomical expected dates for variable Islamic holidays.
 */
final class HijriDateConverterService implements HijriDateConverterInterface
{
    public function hijriToGregorian(int $hijriYear, int $hijriMonth, int $hijriDay): CarbonImmutable
    {
        $month = max(1, min(12, $hijriMonth));
        $day = max(1, min(30, $hijriDay));

        if (class_exists(IntlCalendar::class)) {
            $calendar = IntlCalendar::createInstance(
                IntlTimeZone::getGMT(),
                'en_US@calendar=islamic-umalqura'
            );

            if ($calendar !== null) {
                $calendar->clear();
                $calendar->set($hijriYear, $month - 1, $day, 12, 0, 0);
                $timestampMs = $calendar->getTime();

                if ($timestampMs !== false) {
                    return CarbonImmutable::createFromTimestampUTC((int) floor($timestampMs / 1000))->startOfDay();
                }
            }
        }

        return $this->fallbackHijriToGregorian($hijriYear, $month, $day);
    }

    /**
     * @return array{year: int, month: int, day: int}
     */
    public function gregorianToHijri(CarbonImmutable $date): array
    {
        if (class_exists(IntlCalendar::class)) {
            $calendar = IntlCalendar::createInstance(
                IntlTimeZone::getGMT(),
                'en_US@calendar=islamic-umalqura'
            );

            if ($calendar !== null) {
                $calendar->setTime($date->setTime(12, 0, 0)->getTimestamp() * 1000);

                return [
                    'year'  => (int) $calendar->get(IntlCalendar::FIELD_YEAR),
                    'month' => (int) $calendar->get(IntlCalendar::FIELD_MONTH) + 1,
                    'day'   => (int) $calendar->get(IntlCalendar::FIELD_DAY_OF_MONTH),
                ];
            }
        }

        return $this->fallbackGregorianToHijri($date);
    }

    /**
     * @return array<int, int>
     */
    public function overlappingHijriYears(int $gregorianYear): array
    {
        $startOfYear = CarbonImmutable::createFromDate($gregorianYear, 1, 1)->startOfDay();
        $endOfYear = CarbonImmutable::createFromDate($gregorianYear, 12, 31)->startOfDay();

        $startHijriYear = $this->gregorianToHijri($startOfYear)['year'];
        $endHijriYear = $this->gregorianToHijri($endOfYear)['year'];

        return range(min($startHijriYear, $endHijriYear), max($startHijriYear, $endHijriYear));
    }

    private function fallbackHijriToGregorian(int $year, int $month, int $day): CarbonImmutable
    {
        $julianDay = (int) floor((11 * $year + 3) / 30)
            + 354 * $year
            + 30 * $month
            - (int) floor(($month - 1) / 2)
            + $day
            + 1948440
            - 385;

        $gregorianString = jdtogregorian($julianDay);
        [$gMonth, $gDay, $gYear] = array_map('intval', explode('/', $gregorianString));

        return CarbonImmutable::createFromDate($gYear, $gMonth, $gDay)->startOfDay();
    }

    /**
     * @return array{year: int, month: int, day: int}
     */
    private function fallbackGregorianToHijri(CarbonImmutable $date): array
    {
        $julianDay = gregoriantojd((int) $date->month, (int) $date->day, (int) $date->year);
        $l = $julianDay - 1948440 + 10632;
        $n = (int) floor(($l - 1) / 10631);
        $l = $l - 10631 * $n + 354;
        $j = ((int) floor((10985 - $l) / 5316)) * ((int) floor((50 * $l) / 17719))
            + ((int) floor($l / 5670)) * ((int) floor((43 * $l) / 15238));
        $l = $l - ((int) floor((30 - $j) / 15)) * ((int) floor((17719 * $j) / 50))
            - ((int) floor($j / 16)) * ((int) floor((15238 * $j) / 43)) + 29;
        $month = (int) floor((24 * $l) / 709);
        $day = $l - (int) floor((709 * $month) / 24);
        $year = 30 * $n + $j - 30;

        return [
            'year'  => $year,
            'month' => $month,
            'day'   => $day,
        ];
    }
}
