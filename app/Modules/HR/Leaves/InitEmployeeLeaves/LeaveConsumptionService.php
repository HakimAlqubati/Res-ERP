<?php

namespace App\Modules\HR\Leaves\InitEmployeeLeaves;

use App\Models\EmployeeApplicationV2;
use Illuminate\Support\Collection;

/**
 * Class LeaveConsumptionService
 *
 * Responsible for aggregating the consumed (used) and pending leave days
 * for a set of employees in a single optimized database query,
 * eliminating N+1 problems during leave balance initialization.
 *
 * Grouping key: [employee_id][leave_type_id][year]
 * Month is intentionally ignored — all days within a year are summed together.
 *
 * Data sources:
 *  - hr_employee_applications  (parent, carries status)
 *  - hr_leave_requests         (detail, carries leave_type, days_count, year)
 *
 * @package App\Modules\HR\Leaves\InitEmployeeLeaves
 */
class LeaveConsumptionService
{
    /**
     * Build a consumption map for a chunk of employees.
     *
     * Returns a nested array keyed by:
     *   [employee_id][leave_type_id][year] => ['used' => float, 'pending' => float]
     *
     * A single JOIN query is executed regardless of chunk size,
     * guaranteeing zero N+1 overhead.
     *
     * @param  array<int> $employeeIds  IDs of employees in the current chunk
     * @return array<int, array<int, array<int, array<string, float>>>>
     */
    public static function buildConsumptionMap(array $employeeIds): array
    {
        if (empty($employeeIds)) {
            return [];
        }

        // One query: join applications → leave_requests,
        // filter by employee IDs, leave type, and relevant statuses.
        // NOTE: the column is `leave_type` (stores the leave type ID as a string).
        $rows = EmployeeApplicationV2::query()
            ->join(
                'hr_leave_requests',
                'hr_leave_requests.application_id',
                '=',
                'hr_employee_applications.id'
            )
            ->whereIn('hr_employee_applications.employee_id', $employeeIds)
            ->where('hr_employee_applications.application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST)
            ->whereIn('hr_employee_applications.status', [
                EmployeeApplicationV2::STATUS_APPROVED,
                EmployeeApplicationV2::STATUS_PENDING,
            ])
            ->whereNull('hr_employee_applications.deleted_at')
            ->select([
                'hr_employee_applications.employee_id',
                'hr_employee_applications.status',
                'hr_leave_requests.leave_type',   // stores the leave_type_id as a string
                'hr_leave_requests.year',
                'hr_leave_requests.days_count',
            ])
            ->get();

        return self::mapRowsToConsumptionIndex($rows);
    }

    // ─────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Transform the raw query result-set into a nested lookup index.
     * Aggregates all days within the same year regardless of month.
     *
     * @param  Collection $rows
     * @return array
     */
    private static function mapRowsToConsumptionIndex(Collection $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $employeeId  = (int) $row->employee_id;
            $leaveTypeId = (int) $row->leave_type; // cast string → int
            $year        = (int) $row->year;
            $days        = (float) $row->days_count;

            // Ensure the nested structure exists
            $map[$employeeId][$leaveTypeId][$year] ??= [
                'used'    => 0.0,
                'pending' => 0.0,
            ];

            if ($row->status === EmployeeApplicationV2::STATUS_APPROVED) {
                $map[$employeeId][$leaveTypeId][$year]['used'] += $days;
            } else {
                // STATUS_PENDING
                $map[$employeeId][$leaveTypeId][$year]['pending'] += $days;
            }
        }

        return $map;
    }

    /**
     * Extract the consumed days for a specific balance record from the pre-built map.
     *
     * @param  array  $map          The map returned by buildConsumptionMap()
     * @param  int    $employeeId
     * @param  int    $leaveTypeId
     * @param  int    $year
     * @return array{used: float, pending: float}
     */
    public static function resolve(array $map, int $employeeId, int $leaveTypeId, int $year): array
    {
        return $map[$employeeId][$leaveTypeId][$year] ?? [
            'used'    => 0.0,
            'pending' => 0.0,
        ];
    }
}
