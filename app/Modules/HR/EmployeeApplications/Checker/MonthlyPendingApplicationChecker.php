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

        return $this->queryBuilder->build($filterDto)->exists()
            || $this->queryBuilder->getAdvanceWageQuery($filterDto)->exists()
            || $this->queryBuilder->getOvertimeQuery($filterDto)->exists();
    }

    /**
     * Returns the total count of pending applications from all sources.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalCount(array $filters): int
    {
        $filterDto = CheckerFilterDTO::fromArray($filters);

        return $this->queryBuilder->build($filterDto)->count()
            + $this->queryBuilder->getAdvanceWageQuery($filterDto)->count()
            + $this->queryBuilder->getOvertimeQuery($filterDto)->count();
    }

    /**
     * Provides a final summary for dashboard usage.
     * Returns counts and status without any employee-specific details.
     *
     * @param array $filters
     * @return array
     */
    public function getDashboardSummary(array $filters): array
    {
        $filterDto = CheckerFilterDTO::fromArray($filters);
        
        // 1. Get Application Breakdown
        $breakdown = $this->queryBuilder->build($filterDto)
            ->selectRaw('application_type_id, count(*) as count')
            ->groupBy('application_type_id')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[$item->application_type_id] ?? 'Other',
                    'count' => (int) $item->count,
                ];
            })
            ->toArray();

        // 2. Add Advance Wages
        $wageCount = $this->queryBuilder->getAdvanceWageQuery($filterDto)->count();
        if ($wageCount > 0) {
            $breakdown[] = ['type' => 'Advance Wage', 'count' => (int) $wageCount];
        }

        // 3. Add Overtime
        $overtimeCount = $this->queryBuilder->getOvertimeQuery($filterDto)->count();
        if ($overtimeCount > 0) {
            $breakdown[] = ['type' => 'Overtime', 'count' => (int) $overtimeCount];
        }

        $totalCount = (int) array_sum(array_column($breakdown, 'count'));

        $result = [
            'has_pending' => $totalCount > 0,
            'total_count' => $totalCount,
            'breakdown'   => $breakdown,
            'period'      => "{$filterDto->year}-" . str_pad($filterDto->month, 2, '0', STR_PAD_LEFT),
        ];

        // 4. Branch-wise breakdown (Aggregated from all sources)
        if (!$filterDto->branchId) {
            $result['by_branch'] = $this->getAggregatedBranchBreakdown($filterDto);
        }

        return $result;
    }

    /**
     * Aggregates pending counts by branch from all sources.
     */
    private function getAggregatedBranchBreakdown(CheckerFilterDTO $filterDto): array
    {
        $branches = [];

        // Sources to aggregate
        $sources = [
            $this->queryBuilder->build($filterDto),
            $this->queryBuilder->getAdvanceWageQuery($filterDto),
            $this->queryBuilder->getOvertimeQuery($filterDto)
        ];

        foreach ($sources as $query) {
            $counts = (clone $query)
                ->join('branches', function($join) use ($query) {
                    $table = $query->getModel()->getTable();
                    $join->on("{$table}.branch_id", '=', 'branches.id');
                })
                ->selectRaw('branches.id as branch_id, branches.name as name, count(*) as count')
                ->groupBy('branches.id', 'branches.name')
                ->get();

            foreach ($counts as $row) {
                $id = $row->branch_id;
                if (!isset($branches[$id])) {
                    $branches[$id] = [
                        'branch_id' => $id,
                        'name' => $row->name,
                        'count' => 0
                    ];
                }
                $branches[$id]['count'] += $row->count;
            }
        }

        return array_values($branches);
    }
}
