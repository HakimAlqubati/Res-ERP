<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\DTOs;

use Carbon\Carbon;
use InvalidArgumentException;

final class EmployeeStatementFilterDTO
{
    public function __construct(
        public readonly ?int $employeeId,
        public readonly Carbon $fromDate,
        public readonly Carbon $toDate,
        public readonly ?string $status = 'approved',
    ) {
        if ($this->fromDate->isAfter($this->toDate)) {
            throw new InvalidArgumentException('Start date cannot be after end date.');
        }
    }

    /**
     * Create DTO from an array of raw input data.
     */
    public static function fromArray(array $data): self
    {
        $employeeId = isset($data['employee_id']) && !empty($data['employee_id'])
            ? (int) $data['employee_id']
            : null;

        $fromDate = !empty($data['from_date'])
            ? Carbon::parse($data['from_date'])->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $toDate = !empty($data['to_date'])
            ? Carbon::parse($data['to_date'])->endOfDay()
            : now()->endOfMonth()->endOfDay();

        return new self(
            employeeId: $employeeId,
            fromDate: $fromDate,
            toDate: $toDate,
            status: $data['status'] ?? 'approved',
        );
    }

    public function hasEmployee(): bool
    {
        return !empty($this->employeeId);
    }

    public function getFormattedPeriod(): string
    {
        return $this->fromDate->format('Y-m-d') . ' — ' . $this->toDate->format('Y-m-d');
    }
}
