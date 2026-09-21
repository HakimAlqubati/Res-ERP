<?php

namespace App\Modules\HR\Payroll\Services;

use App\Models\Payroll;
use Carbon\Carbon;
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

        // الغاء ارجاع الموظفين الذين خرجوا من الفرع في نفس الشهر
        if (isset($filters['year']) && isset($filters['month'])) {
            $startOfMonth = Carbon::create((int)$filters['year'], (int)$filters['month'], 1)->startOfMonth()->toDateString();
            $endOfMonth   = Carbon::create((int)$filters['year'], (int)$filters['month'], 1)->endOfMonth()->toDateString();

            $query->whereDoesntHave('employee.branchLogs', function ($q) use ($filters, $startOfMonth, $endOfMonth) {
                if (isset($filters['branch_id'])) {
                    $q->where('branch_id', $filters['branch_id']);
                } else {
                    $q->whereColumn('branch_id', 'hr_payrolls.branch_id');
                }
                $q->whereNotNull('end_at')
                  ->whereBetween('end_at', [$startOfMonth, $endOfMonth]);
            });
        } elseif (isset($filters['branch_id'])) {
            $query->whereDoesntHave('employee.branchLogs', function ($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id'])
                  ->whereNotNull('end_at')
                  ->whereRaw('end_at BETWEEN hr_payrolls.period_start_date AND hr_payrolls.period_end_date');
            });
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
