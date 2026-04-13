<?php

namespace App\Modules\HR\Payroll\Services;

use App\Models\PenaltyDeduction;
use Illuminate\Pagination\LengthAwarePaginator;

class PenaltyDeductionService
{
    /**
     * Retrieve a paginated list of penalty deductions based on filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPenaltiesList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PenaltyDeduction::query()->with([
            'deduction:id,name',
            'employee:id,name',
            'creator:id,name',
            'approver:id,name',
            'rejector:id,name'
        ]);

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (!empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where('description', 'like', "%{$search}%");
        }

        $query->orderBy('date', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Create a new penalty deduction record.
     *
     * @param array $data
     * @return PenaltyDeduction
     */
    public function createPenalty(array $data): PenaltyDeduction
    {
        return PenaltyDeduction::create($data);
    }

    /**
     * Retrieve details of a specific penalty deduction.
     *
     * @param int $id
     * @return PenaltyDeduction|null
     */
    public function getPenaltyById(int $id): ?PenaltyDeduction
    {
        return PenaltyDeduction::with([
            'deduction:id,name',
            'employee:id,name',
            'creator:id,name',
            'approver:id,name',
            'rejector:id,name'
        ])->find($id);
    }
}
