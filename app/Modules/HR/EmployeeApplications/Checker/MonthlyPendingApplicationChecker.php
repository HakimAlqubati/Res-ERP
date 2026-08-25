<?php

namespace App\Modules\HR\EmployeeApplications\Checker;

use App\Models\AdvanceRequest;
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
            || $this->queryBuilder->getOvertimeQuery($filterDto)->exists()
            || $this->queryBuilder->getEmployeeRewardQuery($filterDto)->exists()
            || $this->queryBuilder->getPenaltyDeductionQuery($filterDto)->exists()
            || $this->queryBuilder->getUnpaidPreviousPayrollQuery($filterDto)->exists();
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
            + $this->queryBuilder->getOvertimeQuery($filterDto)->count()
            + $this->queryBuilder->getEmployeeRewardQuery($filterDto)->count()
            + $this->queryBuilder->getPenaltyDeductionQuery($filterDto)->count()
            + $this->queryBuilder->getUnpaidPreviousPayrollQuery($filterDto)->count();
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

        // 4. Add Employee Rewards
        $rewardCount = $this->queryBuilder->getEmployeeRewardQuery($filterDto)->count();
        if ($rewardCount > 0) {
            $breakdown[] = ['type' => 'Employee Reward', 'count' => (int) $rewardCount];
        }

        // 5. Add Penalty Deductions
        $penaltyCount = $this->queryBuilder->getPenaltyDeductionQuery($filterDto)->count();
        if ($penaltyCount > 0) {
            $breakdown[] = ['type' => 'Penalty Deduction', 'count' => (int) $penaltyCount];
        }

        // 6. Add Advance requests approved but not yet approved by finance manager
        $advanceFinancePendingCount = $this->getAdvanceFinanceManagerPendingCount($filterDto);
        if ($advanceFinancePendingCount > 0) {
            $breakdown[] = ['type' => 'Advance Finance Manager Pending', 'count' => (int) $advanceFinancePendingCount];
        }

        // 7. Add Unpaid Previous Payrolls
        $unpaidPayrollCount = $this->queryBuilder->getUnpaidPreviousPayrollQuery($filterDto)->count();
        if ($unpaidPayrollCount > 0) {
            $breakdown[] = ['type' => 'Unpaid Previous Payroll', 'count' => (int) $unpaidPayrollCount];
        }

        $totalCount = (int) array_sum(array_column($breakdown, 'count'));

        $result = [
            'has_pending' => $totalCount > 0,
            'total_count' => $totalCount,
            'breakdown'   => $breakdown,
            'period'      => "{$filterDto->year}-" . str_pad($filterDto->month, 2, '0', STR_PAD_LEFT),
        ];

        // 8. Branch-wise breakdown (Aggregated from all sources)
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
            $this->queryBuilder->getOvertimeQuery($filterDto),
            $this->queryBuilder->getEmployeeRewardQuery($filterDto),
            $this->queryBuilder->getPenaltyDeductionQuery($filterDto),
            $this->queryBuilder->getUnpaidPreviousPayrollQuery($filterDto),
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

    /**
     * Count advance requests that are approved but not yet approved by finance manager.
     */
    private function getAdvanceFinanceManagerPendingCount(CheckerFilterDTO $filterDto): int
    {
        return AdvanceRequest::query()
            ->whereNull('finance_approved_at')
            ->whereHas('application', function ($q) use ($filterDto) {
                $q->whereIn('status', [EmployeeApplicationV2::STATUS_APPROVED, EmployeeApplicationV2::STATUS_PENDING]);

                if ($filterDto->branchId) {
                    $q->where('branch_id', $filterDto->branchId);
                }
            })
            ->when($filterDto->year && $filterDto->month, function ($q) use ($filterDto) {
                $startOfMonth = \Carbon\Carbon::create($filterDto->year, $filterDto->month, 1)->startOfMonth()->toDateString();
                $endOfMonth = \Carbon\Carbon::create($filterDto->year, $filterDto->month, 1)->endOfMonth()->toDateString();

                $q->where('deduction_starts_from', '<=', $endOfMonth)
                    ->where(function ($query) use ($startOfMonth) {
                        $query->where('deduction_ends_at', '>=', $startOfMonth)
                            ->orWhereNull('deduction_ends_at');
                    });
            })
            ->when($filterDto->employeeIds, fn($q) => $q->whereIn('employee_id', $filterDto->employeeIds))
            ->count();
    }
}
