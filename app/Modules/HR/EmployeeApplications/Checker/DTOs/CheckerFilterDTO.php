<?php

namespace App\Modules\HR\EmployeeApplications\Checker\DTOs;

/**
 * Data Transfer Object for pending application filtering criteria.
 */
class CheckerFilterDTO
{
    public function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly ?int $day = null,
        public readonly ?array $employeeIds = null,
        public readonly ?int $branchId = null,
    ) {}

    /**
     * Factory method to create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            year: (int) ($data['year'] ?? now()->year),
            month: (int) ($data['month'] ?? now()->month),
            day: isset($data['day']) ? (int) $data['day'] : null,
            employeeIds: isset($data['employee_ids']) ? (array) $data['employee_ids'] : null,
            branchId: isset($data['branch_id']) ? (int) $data['branch_id'] : null,
        );
    }
}
