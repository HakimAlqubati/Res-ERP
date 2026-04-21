<?php

namespace App\Modules\HR\PayrollReports\Services\Helpers;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\SalaryTransaction;
use App\Modules\HR\PayrollReports\DTOs\PayrollReportFilterDTO;
use Illuminate\Database\Eloquent\Builder;

class PayrollReportQueryHelper
{
    /**
     * Build an optimized query to fetch payroll report data from SalaryTransactions.
     * This avoids N+1 queries and aggregates data at the database level.
     *
     * @param PayrollReportFilterDTO $filter
     * @return Builder
     */
    public static function buildOptimizedQuery(PayrollReportFilterDTO $filter): Builder
    {
        $salaryType      = SalaryTransactionType::TYPE_SALARY->value;
        $allowanceType   = SalaryTransactionType::TYPE_ALLOWANCE->value;
        $bonusType       = SalaryTransactionType::TYPE_BONUS->value;
        $overtimeType    = SalaryTransactionType::TYPE_OVERTIME->value;
        $deductionType   = SalaryTransactionType::TYPE_DEDUCTION->value;
        $penaltyType     = SalaryTransactionType::TYPE_PENALTY->value;
        
        $advanceType     = SalaryTransactionType::TYPE_ADVANCE->value;
        $advanceWageType = SalaryTransactionType::TYPE_ADVANCE_WAGE->value;
        $installType     = SalaryTransactionType::TYPE_INSTALL->value;

        // Base query from SalaryTransaction
        $query = SalaryTransaction::query()
            ->join('hr_payrolls', 'hr_salary_transactions.payroll_id', '=', 'hr_payrolls.id')
            ->join('hr_employees', 'hr_payrolls.employee_id', '=', 'hr_employees.id')
            ->leftJoin('branches', 'hr_payrolls.branch_id', '=', 'branches.id')
            ->whereNull('hr_payrolls.deleted_at')
            ->whereNull('hr_employees.deleted_at')
            ->where('hr_salary_transactions.status', SalaryTransaction::STATUS_APPROVED) // ONLY approved transactions affect net salary
            ->select([
                'hr_payrolls.id as payroll_id',
                'hr_payrolls.employee_id',
                'hr_employees.name as employee_name',
                'hr_employees.employee_no as employee_code',
                'hr_payrolls.branch_id',
                'branches.name as branch_name',
                'hr_payrolls.year',
                'hr_payrolls.month',
                'hr_payrolls.status',
                'hr_payrolls.pay_date',
            ])
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.type = '{$salaryType}' THEN hr_salary_transactions.amount ELSE 0 END) as calculated_base_salary")
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.type = '{$allowanceType}' THEN hr_salary_transactions.amount ELSE 0 END) as calculated_allowances")
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.type = '{$bonusType}' THEN hr_salary_transactions.amount ELSE 0 END) as calculated_bonus")
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.type = '{$overtimeType}' THEN hr_salary_transactions.amount ELSE 0 END) as calculated_overtime")
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.type = '{$deductionType}' THEN hr_salary_transactions.amount ELSE 0 END) as calculated_deductions")
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.type IN ('{$advanceType}', '{$advanceWageType}', '{$installType}') THEN hr_salary_transactions.amount ELSE 0 END) as calculated_advances")
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.type = '{$penaltyType}' THEN hr_salary_transactions.amount ELSE 0 END) as calculated_penalties")
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.operation = '+' THEN hr_salary_transactions.amount ELSE 0 END) as total_additions")
            ->selectRaw("SUM(CASE WHEN hr_salary_transactions.operation = '-' THEN hr_salary_transactions.amount ELSE 0 END) as total_deductions_all")
            ->groupBy([
                'hr_payrolls.id',
                'hr_payrolls.employee_id',
                'hr_employees.name',
                'hr_employees.employee_no',
                'hr_payrolls.branch_id',
                'branches.name',
                'hr_payrolls.year',
                'hr_payrolls.month',
                'hr_payrolls.status',
                'hr_payrolls.pay_date',
            ]);

        // Apply Filters mapped to hr_payrolls and hr_salary_transactions
        if ($filter->branchId !== null) {
            $query->where('hr_payrolls.branch_id', $filter->branchId);
        }

        if ($filter->year !== null) {
            $query->where('hr_payrolls.year', $filter->year);
        }

        if ($filter->month !== null) {
            $query->where('hr_payrolls.month', $filter->month);
        }

        if ($filter->employeeId !== null) {
            $query->where('hr_payrolls.employee_id', $filter->employeeId);
        }

        if ($filter->payrollRunId !== null) {
            $query->where('hr_payrolls.payroll_run_id', $filter->payrollRunId);
        }

        if ($filter->status !== null) {
            $query->where('hr_payrolls.status', $filter->status);
        }

        if ($filter->dateFrom !== null) {
            $query->where('hr_payrolls.pay_date', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->where('hr_payrolls.pay_date', '<=', $filter->dateTo);
        }

        return $query;
    }
}
