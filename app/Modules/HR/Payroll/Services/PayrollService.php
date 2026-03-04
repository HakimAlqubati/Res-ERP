<?php

namespace App\Modules\HR\Payroll\Services;

use App\Models\Payroll;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollService
{
    /**
     * Get a paginated list of payrolls with optional filtering.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPayrolls(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payroll::with(['employee', 'branch']);

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (isset($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Get details of a specific payroll record.
     *
     * @param int $id
     * @return Payroll
     */
    public function getPayrollById(int $id): Payroll
    {
        return Payroll::with(['employee', 'branch', 'transactions'])->findOrFail($id);
    }
}
