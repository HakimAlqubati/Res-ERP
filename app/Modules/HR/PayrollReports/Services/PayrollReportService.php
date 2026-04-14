<?php

namespace App\Modules\HR\PayrollReports\Services;

use App\Models\Payroll;
use App\Modules\HR\PayrollReports\Contracts\PayrollReportServiceInterface;
use App\Modules\HR\PayrollReports\DTOs\PayrollReportFilterDTO;
use App\Modules\HR\PayrollReports\DTOs\PayrollReportItemDTO;
use App\Modules\HR\PayrollReports\DTOs\PayrollReportResultDTO;
use Illuminate\Database\Eloquent\Builder;

class PayrollReportService implements PayrollReportServiceInterface
{
    /**
     * Generate a detailed payroll report based on the provided filters.
     *
     * @param PayrollReportFilterDTO $filter
     * @return PayrollReportResultDTO
     */
    public function generate(PayrollReportFilterDTO $filter): PayrollReportResultDTO
    {
        $query = $this->buildQuery($filter);

        // Fetch all matching records
        $payrolls = $query->get();

        // Map to Item DTOs
        $items = $payrolls->map(fn (Payroll $payroll) => PayrollReportItemDTO::fromModel($payroll));

        // Calculate Grand Totals
        // Depending on precise logic, net_salary is normally calculated from mutations,
        // but here we sum the attributes existing on the Payroll model.
        return new PayrollReportResultDTO(
            items: $items,
            grandTotalBaseSalary: $payrolls->sum('base_salary'),
            grandTotalAllowances: $payrolls->sum('total_allowances'),
            grandTotalBonus: $payrolls->sum('total_bonus'),
            grandTotalOvertime: $payrolls->sum('overtime_amount'),
            grandTotalDeductions: $payrolls->sum('total_deductions'),
            grandTotalAdvances: $payrolls->sum('total_advances'),
            grandTotalPenalties: $payrolls->sum('total_penalties'),
            grandTotalGrossSalary: $payrolls->sum('gross_salary'),
            // Note: In Payroll model net_salary might be accessed via accessor, using strict sum here is okay
            // but for performance we might map over the collection.
            grandTotalNetSalary: $payrolls->sum(fn ($payroll) => $payroll->net_salary ?? 0)
        );
    }

    /**
     * Build the underlying Eloquent query.
     *
     * @param PayrollReportFilterDTO $filter
     * @return Builder
     */
    private function buildQuery(PayrollReportFilterDTO $filter): Builder
    {
        $query = Payroll::query()->with(['employee', 'branch']);

        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
        }

        if ($filter->year !== null) {
            $query->where('year', $filter->year);
        }

        if ($filter->month !== null) {
            $query->where('month', $filter->month);
        }

        if ($filter->employeeId !== null) {
            $query->where('employee_id', $filter->employeeId);
        }

        if ($filter->payrollRunId !== null) {
            $query->where('payroll_run_id', $filter->payrollRunId);
        }

        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }

        if ($filter->dateFrom !== null) {
            $query->where('pay_date', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->where('pay_date', '<=', $filter->dateTo);
        }

        return $query;
    }
}
