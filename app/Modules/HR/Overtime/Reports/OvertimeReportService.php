<?php

declare(strict_types=1);

namespace App\Modules\HR\Overtime\Reports;

use App\Models\EmployeeOvertime;
use App\Modules\HR\Overtime\Reports\DTOs\OvertimeReportFilter;
use Illuminate\Support\Collection;

/**
 * Overtime Report Service.
 *
 * Generates overtime reports based on the provided filter DTO.
 * Adding new filters is as simple as:
 *   1. Add a property to OvertimeReportFilter
 *   2. Add a corresponding `apply*` method here
 *   3. Register it in applyFilters()
 */
class OvertimeReportService
{
    /**
     * Generate the overtime report with pagination.
     *
     * @return array{items: \Illuminate\Contracts\Pagination\LengthAwarePaginator, summary: array}
     */
    public function generate(OvertimeReportFilter $filter): array
    {
        $query = EmployeeOvertime::query()
            ->with(['employee:id,name,employee_no,branch_id', 'employee.branch:id,name', 'approvedBy:id,name']);

        $this->applyFilters($query, $filter);

        // Clone query for full summary before paginating, to get the total matching summary
        $summaryQuery = clone $query;

        $paginatedRecords = $query->orderBy('date', 'desc')
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);

        return [
            'items'   => $paginatedRecords,
            'summary' => $this->buildSummary($summaryQuery),
        ];
    }

    /**
     * Apply all active filters to the query.
     */
    protected function applyFilters($query, OvertimeReportFilter $filter): void
    {
        // Exclude inactive employees
        $query->whereHas('employee', function ($q) {
            $q->where('active', 1);
        });

        if ($filter->branchId !== null) {
            $query->whereHas('employee', function ($q) use ($filter) {
                $q->where('branch_id', $filter->branchId);
            });
        }

        if ($filter->employeeId !== null) {
            $query->where('employee_id', $filter->employeeId);
        }

        if ($filter->dateFrom !== null) {
            $query->whereDate('date', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->whereDate('date', '<=', $filter->dateTo);
        }

        if ($filter->approved !== null) {
            $query->where('approved', $filter->approved);
        }
    }

    /**
     * Build an aggregate summary from the query itself to respect full scope (not just current page).
     */
    protected function buildSummary($query): array
    {
        return [
            'total_records'        => (clone $query)->count(),
            'total_hours'          => round((float) (clone $query)->sum('hours'), 2),
            'approved_count'       => (clone $query)->where('approved', 1)->count(),
            'pending_count'        => (clone $query)->where('approved', 0)->count(),
            'unique_employees'     => (clone $query)->distinct('employee_id')->count('employee_id'),
        ];
    }
}
