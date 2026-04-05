<?php

namespace App\Modules\HR\Overtime\V2\Services;

use App\Models\EmployeeOvertime;
use Illuminate\Support\Collection;

/**
 * Service class for managing Overtime V2 business logic.
 */
class OvertimeService
{
    /**
     * Get overtime records grouped by date.
     *
     * @param array $filters
     * @return Collection
     */
    public function getGroupedByDate(array $filters = []): Collection
    {
        $query = EmployeeOvertime::query()
            ->with(['employee:id,name', 'approvedBy:id,name', 'createdBy:id,name','rejectedBy:id,name']);

        // Apply Filters
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            if (in_array($filters['status'], EmployeeOvertime::STATUSES)) {
                $query->where('status', $filters['status']);
            }
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // We fetch them sorted by Date DESC, then by created ID DESC
        $records = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Group the records by their 'date' column
        // This will result in a Collection where keys are the dates string
        // Ex: ['2023-10-01' => [OvertimeRecord1, OvertimeRecord2]]
        return $records->groupBy('date');
    }

    /**
     * Update overtime hours for a specific record.
     *
     * @param int $id
     * @param float $hours
     * @return EmployeeOvertime
     * @throws \Exception
     */
    public function updateHours(int $id, float $hours): EmployeeOvertime
    {
        $overtime = EmployeeOvertime::findOrFail($id);

        if ($overtime->status === EmployeeOvertime::STATUS_APPROVED) {
            throw new \Exception('Cannot update an approved overtime record. Please undo the approval first.', 403);
        }

        $overtime->update([
            'hours' => $hours,
        ]);

        return $overtime;
    }
}
