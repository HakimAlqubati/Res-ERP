<?php

namespace App\Modules\HR\PayrollReports\Services;

use App\Models\Employee;
use App\Modules\HR\PayrollReports\Contracts\EmployeeFinancialReportServiceInterface;
use App\Modules\HR\PayrollReports\DTOs\EmployeeFinancialSummaryDTO;
use Illuminate\Support\Collection;

class EmployeeFinancialReportService implements EmployeeFinancialReportServiceInterface
{
    public function generateForEmployee(int $employeeId): EmployeeFinancialSummaryDTO
    {
        $employee = Employee::with([
            'branch',
            'monthlyIncentives.monthlyIncentive', 
            'allowances.allowance', 
            'deductions.deduction'
        ])->findOrFail($employeeId);

        return $this->mapToDTO($employee);
    }

    public function generateForEmployees(array $employeeIds): Collection
    {
        $employees = Employee::with([
            'branch',
            'monthlyIncentives.monthlyIncentive', 
            'allowances.allowance', 
            'deductions.deduction'
        ])
        ->whereIn('id', $employeeIds)
        ->where(function ($query) {
            $query->has('monthlyIncentives')
                  ->orHas('allowances')
                  ->orHas('deductions');
        })
        ->get();

        return $employees->map(fn (Employee $employee) => $this->mapToDTO($employee));
    }

    private function mapToDTO(Employee $employee): EmployeeFinancialSummaryDTO
    {
        // Extract names of the assigned types
        $incentiveTypes = $employee->monthlyIncentives
            ->map(fn($item) => $item->monthlyIncentive?->name)
            ->filter()
            ->unique()
            ->implode(' ، ');

        $allowanceTypes = $employee->allowances
            ->map(fn($item) => $item->allowance?->name)
            ->filter()
            ->unique()
            ->implode(' ، ');

        $deductionTypes = $employee->deductions
            ->map(fn($item) => $item->deduction?->name)
            ->filter()
            ->unique()
            ->implode(' ، ');

        return new EmployeeFinancialSummaryDTO(
            employeeId: $employee->id,
            employeeName: $employee->name ?? 'Unknown',
            branchName: $employee->branch?->name ?? 'Unknown',
            incentiveTypes: $incentiveTypes ?: '-',
            allowanceTypes: $allowanceTypes ?: '-',
            deductionTypes: $deductionTypes ?: '-'
        );
    }
}
