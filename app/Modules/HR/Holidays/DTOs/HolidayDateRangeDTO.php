<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\DTOs;

final readonly class HolidayDateRangeDTO
{
    public function __construct(
        public int $year,
        public ?int $hijriYear,
        public string $fromDate,
        public string $toDate,
        public int $countDays,
    ) {}

    /**
     * @return array{year: int, hijri_year: int|null, expected_from_date: string, expected_to_date: string, from_date: string, to_date: string, count_days: int}
     */
    public function toArray(): array
    {
        return [
            'year'               => $this->year,
            'hijri_year'         => $this->hijriYear,
            'expected_from_date' => $this->fromDate,
            'expected_to_date'   => $this->toDate,
            'from_date'          => $this->fromDate,
            'to_date'            => $this->toDate,
            'count_days'         => $this->countDays,
        ];
    }
}
