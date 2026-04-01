<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollDeductionReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollDeductionReportResource;
use App\Modules\HR\Payroll\DTOs\DeductionReportFilterDTO;
use App\Modules\HR\Payroll\Reports\DeductionReport;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Exception;

class ListPayrollDeductionReports extends ListRecords
{
    protected static string $resource = PayrollDeductionReportResource::class;
    
    protected string $view = 'reports.hr.payroll.deduction-report';

    protected function getViewData(): array
    {
        // Extract filters state
        $filters = $this->getTable()->getFilters();
        
        $groupingState = $filters['grouping_filter']->getState() ?? [];
        $groupByState = $groupingState['group_by'] ?? DeductionReportFilterDTO::GROUP_BY_EMPLOYEE;
        $employeeId = $groupingState['employee_id'] ?? null;
        $branchId = $groupingState['branch_id'] ?? null;
        
        // Infer branch from employee if grouped by employee and none selected
        if ($employeeId && !$branchId) {
            $employee = \App\Models\Employee::find($employeeId);
            $branchId = $employee ? $employee->branch_id : null;
        }

        $dateRange = $filters['date_range']->getState();
        $fromDate = $dateRange['from_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $dateRange['to_date'] ?? now()->endOfMonth()->format('Y-m-d');

        // Optional Employer Contribution filter (default to true)
        // If state is null/blank, it implies 'true' since placeholder is 'Yes'. If explicit false, make it false.
        $employerContriState = $filters['include_employer_contribution']->getState()['value'] ?? true;
        $includeEmployerContribution = filter_var($employerContriState, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

        $deductionState = $filters['deduction_type']->getState() ?? [];
        $deductionTypes = $deductionState['values'] ?? (isset($deductionState['value']) ? (array) $deductionState['value'] : null);
        if (empty($deductionTypes)) {
            $deductionTypes = null;
        }

        try {
            // Use DTO to validate and hold data
            $dto = DeductionReportFilterDTO::fromArray([
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'employee_id' => $employeeId,
                'branch_id' => $branchId,
                'include_employer_contribution' => $includeEmployerContribution,
                'deduction_types' => $deductionTypes,
                'group_by' => $groupByState
            ]);

            // Execute the specific report class
            $reportService = new DeductionReport();
            $reportData = $reportService->getSummary($dto);
            $reportData['group_by'] = $groupByState; // For view reference

        } catch (Exception $e) {
            $reportData = null;
            // Optionally log the exception here
        }

        return [
            'reportData' => $reportData,
        ];
    }
    
    public function getView(): string
    {
        $filters = $this->getTable()->getFilters();
        $groupingState = $filters['grouping_filter']->getState() ?? [];
        $groupBy = $groupingState['group_by'] ?? DeductionReportFilterDTO::GROUP_BY_EMPLOYEE;
        
        if ($groupBy === DeductionReportFilterDTO::GROUP_BY_BRANCH) {
            return 'reports.hr.payroll.deduction-report-branch';
        }
        
        return 'reports.hr.payroll.deduction-report';
    }
}
