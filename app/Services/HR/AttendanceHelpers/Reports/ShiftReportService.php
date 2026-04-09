<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\EmployeePeriod;
use Illuminate\Support\Collection;

/**
 * ShiftReportService
 *
 * Retrieves employees currently assigned to a given work period (shift)
 * in a specific branch, by querying hr_employee_periods and their related
 * employee records.
 */
class ShiftReportService
{
    /**
     * Get employees assigned to the given shift (period) in the given branch.
     *
     * Filters:
     *  - branch_id  (required)
     *  - period_id  (required)
     *
     * @param  array  $filters  ['branch_id' => int, 'period_id' => int]
     * @return Collection
     */
    public function getEmployeesInShift(array $filters = []): Collection
    {
        $branchId  = $filters['branch_id'] ?? null;
        $periodIds = $filters['period_id'] ?? null;

        if (! $branchId || empty($periodIds)) {
            return collect([]);
        }

        $periodIds = (array) $periodIds;

        return EmployeePeriod::query()
            ->with([
                'employee:id,name,branch_id,department_id,active',
                'workPeriod:id,name,start_at,end_at',
                'days',
            ])
            ->whereIn('period_id', $periodIds)
            ->whereHas(
                'employee',
                fn ($q) => $q
                    ->where('branch_id', (int) $branchId)
                    ->where('active', 1)
            )
            ->where(function ($q) {
                // active assignment = no end_date OR end_date >= today
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->get()
            // deduplicate by employee (an employee could have multiple open rows)
            ->unique('employee_id')
            ->values()
            ->map(fn ($ep) => [
                'employee_id'     => $ep->employee_id,
                'employee_name'   => $ep->employee?->name,
                'branch_id'       => $ep->employee?->branch_id,
                'period_id'       => $ep->period_id,
                'period_name'     => $ep->workPeriod?->name,
                'period_start_at' => $ep->workPeriod?->start_at,
                'period_end_at'   => $ep->workPeriod?->end_at,
                'start_date'      => $ep->start_date,
                'end_date'        => $ep->end_date,
                'days'            => $ep->days->pluck('day_of_week')->toArray(),
            ]);
    }
}
