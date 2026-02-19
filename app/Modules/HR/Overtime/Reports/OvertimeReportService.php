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
     * Generate the overtime report.
     *
     * @return array{items: Collection, summary: array}
     */
    public function generate(OvertimeReportFilter $filter): array
    {
        $query = EmployeeOvertime::query()
            ->with(['employee:id,name,employee_no,branch_id', 'employee.branch:id,name', 'approvedBy:id,name']);

        $this->applyFilters($query, $filter);

        $records = $query->orderBy('date', 'desc')->get();

        return [
            'items'   => $records,
            'summary' => $this->buildSummary($records),
        ];
    }

    /**
     * Apply all active filters to the query.
     */
    protected function applyFilters($query, OvertimeReportFilter $filter): void
    {
        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
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
     * Build a summary from the report records.
     */
    protected function buildSummary(Collection $records): array
    {
        return [
            'total_records'        => $records->count(),
            'total_hours'          => round($records->sum('hours'), 2),
            'approved_count'       => $records->where('approved', 1)->count(),
            'pending_count'        => $records->where('approved', 0)->count(),
            'unique_employees'     => $records->pluck('employee_id')->unique()->count(),
        ];
    }
}
