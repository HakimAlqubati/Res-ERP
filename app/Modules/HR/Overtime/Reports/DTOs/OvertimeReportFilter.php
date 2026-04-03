<?php

declare(strict_types=1);

namespace App\Modules\HR\Overtime\Reports\DTOs;

/**
 * Immutable DTO for overtime report filtering.
 *
 * Add new properties here to extend filtering capabilities
 * without changing the service signature.
 */
final class OvertimeReportFilter
{
    public function __construct(
        public readonly ?int $branchId = null,
        public readonly ?int $employeeId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $status = null,
        public readonly int $perPage = 15,
        public readonly int $page = 1,
    ) {
        if ($this->status !== null && !in_array($this->status, \App\Models\EmployeeOvertime::STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$this->status}. Must be one of: " . implode(', ', \App\Models\EmployeeOvertime::STATUSES));
        }
    }

    /**
     * Create from an associative array (e.g. request input).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            branchId: isset($data['branch_id'])    ? (int) $data['branch_id']    : null,
            employeeId: isset($data['employee_id'])  ? (int) $data['employee_id']  : null,
            dateFrom: $data['date_from']  ?? null,
            dateTo: $data['date_to']    ?? null,
            status: $data['status']     ?? null,
            perPage: isset($data['per_page'])     ? (int) $data['per_page'] : 15,
            page: isset($data['page'])         ? (int) $data['page'] : 1,
        );
    }

    /**
     * Check if any filter is active.
     */
    public function hasFilters(): bool
    {
        return $this->branchId !== null
            || $this->employeeId !== null
            || $this->dateFrom !== null
            || $this->dateTo !== null
            || $this->status !== null;
    }
}
