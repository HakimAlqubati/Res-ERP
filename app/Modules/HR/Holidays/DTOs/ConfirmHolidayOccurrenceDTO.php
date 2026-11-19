<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\DTOs;

final readonly class ConfirmHolidayOccurrenceDTO
{
    public function __construct(
        public string $fromDate,
        public string $toDate,
        public int $countDays,
        public ?int $actorId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?int $actorId = null): self
    {
        return new self(
            fromDate: (string) $data['from_date'],
            toDate: (string) $data['to_date'],
            countDays: max(1, (int) ($data['count_days'] ?? 1)),
            actorId: $actorId,
        );
    }
}
