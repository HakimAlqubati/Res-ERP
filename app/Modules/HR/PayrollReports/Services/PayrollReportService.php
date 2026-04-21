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
        // Use the optimized helper to query securely directly from SalaryTransaction
        $query = \App\Modules\HR\PayrollReports\Services\Helpers\PayrollReportQueryHelper::buildOptimizedQuery($filter);
        
        // Fetch all matching aggregated records
        $aggregatedRows = $query->get();
       

        // Map to Item DTOs
        $items = $aggregatedRows->map(fn ($row) => PayrollReportItemDTO::fromAggregatedRow($row));
        // Calculate Grand Totals utilizing the DTOs
        return new PayrollReportResultDTO(
            items: $items,
            grandTotalBaseSalary: $items->sum('baseSalary'),
            grandTotalAllowances: $items->sum('totalAllowances'),
            grandTotalBonus: $items->sum('totalBonus'),
            grandTotalOvertime: $items->sum('totalOvertime'),
            grandTotalDeductions: $items->sum('totalDeductions'),
            grandTotalAdvances: $items->sum('totalAdvances'),
            grandTotalPenalties: $items->sum('totalPenalties'),
            grandTotalGrossSalary: $items->sum('grossSalary'),
            grandTotalNetSalary: $items->sum('netSalary')
        );
    }
}
