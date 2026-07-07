<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PrePayrollDeductionReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PrePayrollDeductionReportResource;
use App\Modules\HR\Payroll\DTOs\PrePayrollDeductionFilterDTO;
use App\Modules\HR\Payroll\Reports\PrePayrollDeductionReport;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListPrePayrollDeductionReports extends ListRecords
{
    protected static string $resource = PrePayrollDeductionReportResource::class;

    protected string $view = 'reports.hr.payroll.pre-payroll-deduction-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();

        $groupingState = $filters['grouping_filter']->getState() ?? [];
        $groupBy       = $groupingState['group_by'] ?? PrePayrollDeductionFilterDTO::GROUP_BY_EMPLOYEE;

        $employeeId = $groupBy === PrePayrollDeductionFilterDTO::GROUP_BY_EMPLOYEE
            ? ($groupingState['employee_id'] ?? null)
            : null;

        $branchId = $groupBy === PrePayrollDeductionFilterDTO::GROUP_BY_BRANCH
            ? ($groupingState['branch_id'] ?? null)
            : null;

        // استنتاج الفرع من الموظف إذا لم يُحدد صراحةً
        if ($employeeId && ! $branchId) {
            $branchId = \App\Models\Employee::find($employeeId)?->branch_id;
        }

        $periodState = $filters['period']->getState();
        $year        = (int) ($periodState['year']  ?? now()->year);
        $month       = (int) ($periodState['month'] ?? now()->month);

        try {
            $dto = PrePayrollDeductionFilterDTO::fromArray([
                'year'        => $year,
                'month'       => $month,
                'employee_id' => $employeeId,
                'branch_id'   => $branchId,
                'group_by'    => $groupBy,
            ]);

            /** @var PrePayrollDeductionReport $report */
            $report     = app(PrePayrollDeductionReport::class);
            $reportData = $report->getSummary($dto);
            $reportData['group_by'] = $groupBy;

        } catch (Throwable) {
            $reportData = null;
        }

        return [
            'reportData' => $reportData,
        ];
    }

    public function getView(): string
    {
        return $this->view;
    }
}
