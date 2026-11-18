<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\DTOs;

use App\Models\SalaryIncreaseRequest;

final class SalaryIncreaseDataDTO
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $increaseType,
        public readonly float $value,
        public readonly ?string $effectiveDate = null,
        public readonly ?string $notes = null,
        public readonly ?int $actorId = null,
    ) {}

    public static function fromArray(array $data, ?int $actorId = null): self
    {
        $type = (string) ($data['increase_type'] ?? SalaryIncreaseRequest::INCREASE_TYPE_FIXED);

        $value = match ($type) {
            SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE => (float) ($data['increase_percentage'] ?? $data['value'] ?? 0),
            SalaryIncreaseRequest::INCREASE_TYPE_NEW_SALARY => (float) ($data['new_salary'] ?? $data['value'] ?? 0),
            default => (float) ($data['increase_amount'] ?? $data['value'] ?? 0),
        };

        return new self(
            employeeId: (int) $data['employee_id'],
            increaseType: $type,
            value: $value,
            effectiveDate: $data['effective_date'] ?? null,
            notes: $data['notes'] ?? null,
            actorId: $actorId ?? auth()->id(),
        );
    }
}
