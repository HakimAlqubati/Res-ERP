<?php

namespace App\Modules\HR\EmployeeApplications\Checker;

use App\Models\EmployeeApplicationV2;
use App\Modules\HR\EmployeeApplications\Checker\DTOs\CheckerFilterDTO;
use App\Modules\HR\EmployeeApplications\Checker\Queries\PendingApplicationQuery;
use Illuminate\Support\Collection;

/**
 * Orchestrator service for checking pending applications.
 * Uses specialized DTOs and Query objects to maintain clean, scalable code.
 */
class MonthlyPendingApplicationChecker
{
    public function __construct(
        private readonly PendingApplicationQuery $queryBuilder
    ) {}

    /**
     * Entry point to verify if any pending applications exist for the given filters.
     *
     * @param array $filters ['year', 'month', 'employee_ids' => [], 'branch_id' => int]
     * @return bool
     */
    public function check(array $filters): bool
    {
        $filterDto = CheckerFilterDTO::fromArray($filters);
        return $this->queryBuilder->build($filterDto)->exists();
    }
}
