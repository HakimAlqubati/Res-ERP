<?php

namespace App\Services\HR\AttendanceHelpers\Reports\DTOs;

/**
 * Represents a single employee who should be present now but has not checked in yet.
 */
final readonly class ExpectedAbsentEmployeeDTO implements \JsonSerializable
{
    public function __construct(
        public int     $employeeId,
        public ?string $employeeName,
        public ?int    $branchId,
        public ?int    $periodId,
        public ?string $periodName,
        public ?string $periodStartAt,
        public ?string $periodEndAt,
    ) {}

    public static function fromEmployeePeriod(\App\Models\EmployeePeriod $ep): self
    {
        return new self(
            employeeId: $ep->employee_id,
            employeeName: $ep->employee?->name,
            branchId: $ep->employee?->branch_id,
            periodId: $ep->period_id,
            periodName: $ep->workPeriod?->name,
            periodStartAt: $ep->workPeriod?->start_at,
            periodEndAt: $ep->workPeriod?->end_at,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'employee_id'     => $this->employeeId,
            'employee_name'   => $this->employeeName,
            'branch_id'       => $this->branchId,
            'period_id'       => $this->periodId,
            'period_name'     => $this->periodName,
            'period_start_at' => $this->periodStartAt,
            'period_end_at'   => $this->periodEndAt,
        ];
    }
}
