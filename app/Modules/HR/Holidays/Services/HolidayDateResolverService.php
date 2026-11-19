<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Services;

use App\Models\HolidayTemplate;
use App\Modules\HR\Holidays\Builders\HolidayOccurrencePayloadBuilder;
use App\Modules\HR\Holidays\Contracts\HijriDateConverterInterface;
use App\Modules\HR\Holidays\DTOs\HolidayDateRangeDTO;
use Carbon\CarbonImmutable;

/**
 * Resolves a HolidayTemplate into one or more expected Gregorian date ranges
 * for a given Gregorian year.
 */
final class HolidayDateResolverService
{
    public function __construct(
        private readonly HijriDateConverterInterface $hijriConverter,
    ) {}

    /**
     * Resolve all occurrences of a template falling within the target Gregorian year.
     *
     * @return array<int, HolidayDateRangeDTO>
     */
    public function resolveForYear(HolidayTemplate $template, int $gregorianYear): array
    {
        if ($template->isFixed()) {
            return [$this->resolveFixedTemplate($template, $gregorianYear)];
        }

        return $this->resolveHijriTemplate($template, $gregorianYear);
    }

    /**
     * Resolve a single primary expected date range for form previewing.
     */
    public function resolvePrimaryForYear(HolidayTemplate $template, int $gregorianYear): HolidayDateRangeDTO
    {
        $resolved = $this->resolveForYear($template, $gregorianYear);

        if (! empty($resolved)) {
            return $resolved[0];
        }

        $hijriYears = $this->hijriConverter->overlappingHijriYears($gregorianYear);
        $fallbackHijriYear = $hijriYears[0];
        $expectedStart = $this->hijriConverter->hijriToGregorian(
            $fallbackHijriYear,
            (int) $template->month,
            (int) $template->day
        );
        $range = HolidayOccurrencePayloadBuilder::calculateFromStartAndDays(
            $expectedStart->toDateString(),
            max(1, (int) $template->count_days)
        );

        return new HolidayDateRangeDTO(
            year: $gregorianYear,
            hijriYear: $fallbackHijriYear,
            fromDate: $range['from_date'],
            toDate: $range['to_date'],
            countDays: $range['count_days'],
        );
    }

    private function resolveFixedTemplate(HolidayTemplate $template, int $gregorianYear): HolidayDateRangeDTO
    {
        $month = max(1, min(12, (int) $template->month));
        $maxDayInMonth = CarbonImmutable::createFromDate($gregorianYear, $month, 1)->daysInMonth;
        $day = max(1, min($maxDayInMonth, (int) $template->day));

        $startDate = CarbonImmutable::createFromDate($gregorianYear, $month, $day)->toDateString();
        $range = HolidayOccurrencePayloadBuilder::calculateFromStartAndDays(
            $startDate,
            max(1, (int) $template->count_days)
        );

        return new HolidayDateRangeDTO(
            year: $gregorianYear,
            hijriYear: null,
            fromDate: $range['from_date'],
            toDate: $range['to_date'],
            countDays: $range['count_days'],
        );
    }

    /**
     * @return array<int, HolidayDateRangeDTO>
     */
    private function resolveHijriTemplate(HolidayTemplate $template, int $gregorianYear): array
    {
        $results = [];
        $hijriYears = $this->hijriConverter->overlappingHijriYears($gregorianYear);

        foreach ($hijriYears as $hijriYear) {
            $expectedStart = $this->hijriConverter->hijriToGregorian(
                $hijriYear,
                (int) $template->month,
                (int) $template->day
            );

            if ((int) $expectedStart->year !== $gregorianYear) {
                continue;
            }

            $range = HolidayOccurrencePayloadBuilder::calculateFromStartAndDays(
                $expectedStart->toDateString(),
                max(1, (int) $template->count_days)
            );

            $results[] = new HolidayDateRangeDTO(
                year: $gregorianYear,
                hijriYear: $hijriYear,
                fromDate: $range['from_date'],
                toDate: $range['to_date'],
                countDays: $range['count_days'],
            );
        }

        return $results;
    }
}
