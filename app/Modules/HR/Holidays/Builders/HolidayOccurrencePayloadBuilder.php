<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Builders;

use App\Models\HolidayOccurrence;
use App\Models\HolidayTemplate;
use App\Modules\HR\Holidays\DTOs\ConfirmHolidayOccurrenceDTO;
use App\Modules\HR\Holidays\DTOs\HolidayDateRangeDTO;
use Carbon\CarbonImmutable;

/**
 * Fluent Builder & single source of truth for normalizing holiday occurrence
 * dates, durations, branch scopes, and persistence payloads across Services and Filament.
 */
final class HolidayOccurrencePayloadBuilder
{
    private ?HolidayTemplate $template = null;
    private ?HolidayDateRangeDTO $dateRange = null;
    private ?int $actorId = null;

    public static function make(): self
    {
        return new self();
    }

    public function fromTemplate(HolidayTemplate $template): self
    {
        $clone = clone $this;
        $clone->template = $template;

        return $clone;
    }

    public function withDateRange(HolidayDateRangeDTO $dateRange): self
    {
        $clone = clone $this;
        $clone->dateRange = $dateRange;

        return $clone;
    }

    public function byActor(?int $actorId): self
    {
        $clone = clone $this;
        $clone->actorId = $actorId ?? auth()->id();

        return $clone;
    }

    /**
     * Calculate [from_date, to_date, count_days, year] given a start date and number of days.
     *
     * @return array{from_date: string, to_date: string, count_days: int, year: int}
     */
    public static function calculateFromStartAndDays(string $fromDate, int $countDays): array
    {
        $days = max(1, $countDays);
        $start = CarbonImmutable::parse($fromDate)->startOfDay();
        $end = $start->addDays($days - 1);

        return [
            'from_date'  => $start->toDateString(),
            'to_date'    => $end->toDateString(),
            'count_days' => $days,
            'year'       => (int) $start->year,
        ];
    }

    /**
     * Calculate [from_date, to_date, count_days, year] given a start date and end date.
     *
     * @return array{from_date: string, to_date: string, count_days: int, year: int}
     */
    public static function calculateFromDateRange(string $fromDate, string $toDate): array
    {
        $start = CarbonImmutable::parse($fromDate)->startOfDay();
        $end = CarbonImmutable::parse($toDate)->startOfDay();

        if ($end->lt($start)) {
            $end = $start;
        }

        return [
            'from_date'  => $start->toDateString(),
            'to_date'    => $end->toDateString(),
            'count_days' => (int) $start->diffInDays($end) + 1,
            'year'       => (int) $start->year,
        ];
    }

    /**
     * Shift a date range by ±N days while preserving its duration.
     *
     * @return array{from_date: string, to_date: string, count_days: int, year: int}
     */
    public static function shiftRange(string $fromDate, int $countDays, int $daysOffset): array
    {
        $shiftedStart = CarbonImmutable::parse($fromDate)->startOfDay()->addDays($daysOffset);

        return self::calculateFromStartAndDays($shiftedStart->toDateString(), $countDays);
    }

    /**
     * @param  array<int, int|string>|null  $branchIds
     * @return array<int, int>|null
     */
    public static function normalizeBranchIds(bool $allBranches, ?array $branchIds): ?array
    {
        if ($allBranches || empty($branchIds)) {
            return null;
        }

        return array_values(array_unique(array_map('intval', $branchIds)));
    }

    /**
     * Build payload when auto-generating an occurrence from a template.
     *
     * @return array<string, mixed>
     */
    public function buildGeneratedPayload(): array
    {
        $status = $this->template->isHijri()
            ? HolidayOccurrence::STATUS_TENTATIVE
            : HolidayOccurrence::STATUS_CONFIRMED;

        $actor = $this->actorId ?? auth()->id();

        return [
            'holiday_template_id' => $this->template->id,
            'name'                => $this->template->name,
            'type'                => $this->template->type,
            'year'                => $this->dateRange->year,
            'hijri_year'          => $this->dateRange->hijriYear,
            'expected_from_date'  => $this->dateRange->fromDate,
            'expected_to_date'    => $this->dateRange->toDate,
            'from_date'           => $this->dateRange->fromDate,
            'to_date'             => $this->dateRange->toDate,
            'count_days'          => $this->dateRange->countDays,
            'status'              => $status,
            'all_branches'        => $this->template->all_branches,
            'branch_ids'          => self::normalizeBranchIds($this->template->all_branches, $this->template->branch_ids),
            'active'              => true,
            'notes'               => $this->template->notes,
            'created_by'          => $actor,
            'updated_by'          => $actor,
            'confirmed_by'        => $status === HolidayOccurrence::STATUS_CONFIRMED ? $actor : null,
            'confirmed_at'        => $status === HolidayOccurrence::STATUS_CONFIRMED ? now() : null,
        ];
    }

    /**
     * Normalize form data for create/update operations.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function buildPersistencePayload(array $data, ?HolidayOccurrence $existing = null): array
    {
        $actor = $this->actorId ?? auth()->id();
        $range = self::calculateFromDateRange(
            (string) $data['from_date'],
            (string) $data['to_date']
        );

        $allBranches = (bool) ($data['all_branches'] ?? true);
        $status = (string) ($data['status'] ?? HolidayOccurrence::STATUS_TENTATIVE);

        $payload = [
            'holiday_template_id' => ! empty($data['holiday_template_id']) ? (int) $data['holiday_template_id'] : null,
            'name'                => (string) $data['name'],
            'type'                => (string) ($data['type'] ?? HolidayTemplate::TYPE_FIXED),
            'year'                => (int) ($data['year'] ?? $range['year']),
            'hijri_year'          => ! empty($data['hijri_year']) ? (int) $data['hijri_year'] : null,
            'expected_from_date'  => $data['expected_from_date'] ?? ($existing?->expected_from_date?->toDateString() ?? $range['from_date']),
            'expected_to_date'    => $data['expected_to_date'] ?? ($existing?->expected_to_date?->toDateString() ?? $range['to_date']),
            'from_date'           => $range['from_date'],
            'to_date'             => $range['to_date'],
            'count_days'          => $range['count_days'],
            'status'              => $status,
            'all_branches'        => $allBranches,
            'branch_ids'          => self::normalizeBranchIds($allBranches, $data['branch_ids'] ?? null),
            'active'              => (bool) ($data['active'] ?? true),
            'notes'               => $data['notes'] ?? null,
            'updated_by'          => $actor,
        ];

        if (! $existing) {
            $payload['created_by'] = $actor;
        }

        if ($status === HolidayOccurrence::STATUS_CONFIRMED) {
            $payload['confirmed_by'] = $existing?->confirmed_by ?? $actor;
            $payload['confirmed_at'] = $existing?->confirmed_at ?? now();
        } elseif ($status === HolidayOccurrence::STATUS_TENTATIVE) {
            $payload['confirmed_by'] = null;
            $payload['confirmed_at'] = null;
        }

        return $payload;
    }

    /**
     * Build payload when confirming (and optionally adjusting) an occurrence.
     *
     * @return array<string, mixed>
     */
    public function buildConfirmationPayload(ConfirmHolidayOccurrenceDTO $dto): array
    {
        $actor = $dto->actorId ?? $this->actorId ?? auth()->id();
        $range = self::calculateFromDateRange($dto->fromDate, $dto->toDate);

        return [
            'from_date'    => $range['from_date'],
            'to_date'      => $range['to_date'],
            'count_days'   => $range['count_days'],
            'year'         => $range['year'],
            'status'       => HolidayOccurrence::STATUS_CONFIRMED,
            'confirmed_by' => $actor,
            'confirmed_at' => now(),
            'updated_by'   => $actor,
        ];
    }
}
