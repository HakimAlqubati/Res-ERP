<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollReportResource;
use App\Modules\HR\PayrollReports\DTOs\PayrollReportFilterDTO;
use App\Modules\HR\PayrollReports\Services\PayrollReportService;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Exception;

class ListPayrollReports extends ListRecords
{
    protected static string $resource = PayrollReportResource::class;
    
    protected string $view = 'reports.hr.payroll.payroll-report';

    protected function getViewData(): array
    {
        // Extract filters state
        $filters = $this->getTable()->getFilters();
        $filterState = $filters['payroll_filter']->getState() ?? [];
        
        $branchId = $filterState['branch_id'] ?? null;
        $period = $filterState['period'] ?? null;

        $reportData = null;
        $branchName = null;

        if ($branchId && $period) {
            $branch = \App\Models\Branch::find($branchId);
            $branchName = $branch?->name;

            try {
                // Parse period (e.g., "April 2026")
                $date = Carbon::parse("1 $period");
                $month = $date->month;
                $year = $date->year;

                $filterDto = new PayrollReportFilterDTO(
                    branchId: (int) $branchId,
                    month: $month,
                    year: $year
                );

                $service = new PayrollReportService();
                $reportData = $service->generate($filterDto);

            } catch (Exception $e) {
                // Return null if parsing or generation fails
                $reportData = null;
            }
        }

        return [
            'reportData' => $reportData,
            'branchId'   => $branchId,
            'branchName' => $branchName,
            'period'     => $period,
        ];
    }
}
