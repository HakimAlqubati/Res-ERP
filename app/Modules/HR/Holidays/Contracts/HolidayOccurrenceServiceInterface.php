<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Contracts;

use App\Models\HolidayOccurrence;
use App\Models\HolidayTemplate;
use App\Modules\HR\Holidays\DTOs\ConfirmHolidayOccurrenceDTO;
use App\Modules\HR\Holidays\DTOs\HolidayDateRangeDTO;

interface HolidayOccurrenceServiceInterface
{
    /**
     * Preview the expected date range for a template in a target Gregorian year.
     */
    public function previewForTemplateAndYear(HolidayTemplate $template, int $gregorianYear): HolidayDateRangeDTO;

    /**
     * Generate missing holiday occurrences for all active templates in a target Gregorian year.
     *
     * @return int Number of occurrences created.
     */
    public function generateForYear(int $gregorianYear, ?int $actorId = null): int;

    /**
     * Create a new holiday occurrence using normalized payload builder logic.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId = null): HolidayOccurrence;

    /**
     * Update an existing holiday occurrence using normalized payload builder logic.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(HolidayOccurrence $occurrence, array $data, ?int $actorId = null): HolidayOccurrence;

    /**
     * Confirm (and optionally adjust) the actual Gregorian dates of an occurrence.
     */
    public function confirm(HolidayOccurrence $occurrence, ConfirmHolidayOccurrenceDTO $dto): HolidayOccurrence;

    /**
     * Shift the actual Gregorian start & end dates of an occurrence by ±N days.
     */
    public function shiftDays(HolidayOccurrence $occurrence, int $daysOffset, ?int $actorId = null): HolidayOccurrence;
}
