<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeAttendanceReportResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeDeductoinSummaryResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\DeductionService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeDeductionSummaryReports extends ListRecords
{
    protected static string $resource = EmployeeDeductoinSummaryResource::class;
    protected static string $view = 'reports.deductions.deductions';

    protected function getViewData(): array
    {
        $employeeId = $this->getTable()->getFilters()['employee_id']->getState()['value'];
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value'];
        $year = $this->getTable()->getFilters()['year']->getState()['value'];
        $deductionService = new DeductionService();
        $employee = Employee::find($employeeId);
        $branch = Branch::find($branchId);
        $deductions = [];
        if (!is_null($branchId)) {
            $deductions = $deductionService->getDeductionsForBranchByYear($branchId, $year);
        } else if (!is_null($employeeId)) {

            $deductions = $deductionService->getDeductionsForEmployeeByYear($employeeId, $year);
        }
        // dd($deductions);
        return  [
            'last_month_name' => $deductions['last_month_name'] ?? null,
            'employeeId' => $employeeId,
            'branchId' => $branchId,
            'year' => $year,
            'employee' => $employee,
            'branch' => $branch,
            'lastMonthDeductions' => $deductions['last_month_deductions'] ?? [],
            'totalDeductions' => $deductions['total_deductions'] ?? [],
        ];
    }
}
