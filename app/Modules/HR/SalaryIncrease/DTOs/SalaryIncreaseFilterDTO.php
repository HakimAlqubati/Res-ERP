<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\DTOs;

final class SalaryIncreaseFilterDTO
{
    public function __construct(
        public readonly ?int $employeeId = null,
        public readonly ?int $branchId = null,
        public readonly ?string $status = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $search = null,
        public readonly bool $applyScopes = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: isset($data['employee_id']) && $data['employee_id'] !== '' ? (int) $data['employee_id'] : null,
            branchId: isset($data['branch_id']) && $data['branch_id'] !== '' ? (int) $data['branch_id'] : null,
            status: ! empty($data['status']) ? (string) $data['status'] : null,
            dateFrom: ! empty($data['date_from']) ? (string) $data['date_from'] : null,
            dateTo: ! empty($data['date_to']) ? (string) $data['date_to'] : null,
            search: ! empty($data['search']) ? (string) $data['search'] : null,
            applyScopes: (bool) ($data['apply_scopes'] ?? true),
        );
    }
}
