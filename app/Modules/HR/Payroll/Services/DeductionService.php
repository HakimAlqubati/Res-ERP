<?php

namespace App\Modules\HR\Payroll\Services;

use App\Models\Deduction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DeductionService
{
    /**
     * Get filtered list of deductions.
     *
     * @param array $filters
     * @param int|string $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getDeductions(array $filters = [], $perPage = 15)
    {
        $query = Deduction::query();

        // Standard Filters
        if (isset($filters['active'])) {
            $query->where('active', (bool) $filters['active']);
        }

        if (isset($filters['is_penalty'])) {
            $query->where('is_penalty', (bool) $filters['is_penalty']);
        }

        if (isset($filters['is_monthly'])) {
            $query->where('is_monthly', (bool) $filters['is_monthly']);
        }

        if (isset($filters['is_mtd_deduction'])) {
            $query->where('is_mtd_deduction', (bool) $filters['is_mtd_deduction']);
        }

        // Search by name
        if (!empty($filters['q'])) {
            $query->where('name', 'like', "%{$filters['q']}%");
        }

        $query->orderBy('name', 'asc');

        if ($perPage === 'all') {
            return $query->get();
        }

        return $query->paginate((int) $perPage);
    }

    /**
     * Get a specific deduction by ID.
     *
     * @param int $id
     * @return Deduction|null
     */
    public function getById(int $id): ?Deduction
    {
        return Deduction::find($id);
    }
}
