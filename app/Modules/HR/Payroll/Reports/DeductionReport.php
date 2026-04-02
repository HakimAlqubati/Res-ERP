<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Reports;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\SalaryTransaction;
use App\Modules\HR\Payroll\DTOs\DeductionReportFilterDTO;
use Illuminate\Support\Collection;

class DeductionReport
{
    /**
     * Get the summary of deductions based on the provided filters.
     *
     * @param DeductionReportFilterDTO $filters
     * @return array
     */
    public function getSummary(DeductionReportFilterDTO $filters): array
    {
        $query = SalaryTransaction::query()
            ->with(['employee']) // Eager load if needed, but we're mostly aggregating
            ->where('status', SalaryTransaction::STATUS_APPROVED)
            ->whereBetween('date', [
                $filters->fromDate->startOfDay()->format('Y-m-d H:i:s'),
                $filters->toDate->endOfDay()->format('Y-m-d H:i:s')
            ]);

        // Filter operations and types
        $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($filters) {
            $q->where('operation', SalaryTransaction::OPERATION_SUB);
            
            if ($filters->includeEmployerContribution) {
                $q->orWhere('type', SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION);
            }
        });

        if ($filters->groupBy === DeductionReportFilterDTO::GROUP_BY_EMPLOYEE) {
            if ($filters->employeeId) {
                $query->where('employee_id', $filters->employeeId);
            }
        } elseif ($filters->groupBy === DeductionReportFilterDTO::GROUP_BY_BRANCH) {
            if ($filters->branchId) {
                $query->whereHas('employee', function ($q) use ($filters) {
                    $q->where('branch_id', $filters->branchId);
                });
            }
        }

        /** @var Collection<int, SalaryTransaction> $transactions */
        $transactions = $query->get();

        if (!empty($filters->deductionTypes)) {
            $transactions = $transactions->filter(function (SalaryTransaction $tx) use ($filters) {
                $name = $tx->description ?: ucfirst(str_replace('_', ' ', $tx->sub_type ?? $tx->type));
                return in_array($name, $filters->deductionTypes);
            })->values();
        }

        if ($transactions->isEmpty()) {
            return $this->emptyResponse($filters);
        }

        // Aggregate deductions by Year-Month, then by description
        $monthlyDeductions = $transactions->groupBy(function (SalaryTransaction $tx) {
            return \Carbon\Carbon::parse($tx->date)->format('Y-m');
        })->sortKeys()->map(function ($monthTransactions, $monthKey) {
            $deductionsList = $monthTransactions->groupBy(function (SalaryTransaction $tx) {
                return $tx->description ?: ucfirst(str_replace('_', ' ', $tx->sub_type ?? $tx->type));
            })->map(function ($group, $name) {
                return [
                    'deduction_name' => $name,
                    'deduction_amount' => round(abs((float) $group->sum('amount')), 2)
                ];
            })->values()->all();

            return [
                'month' => $monthKey,
                'month_name' => \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('F Y'),
                'deductions_list' => $deductionsList,
                'month_total' => round(abs((float) $monthTransactions->sum('amount')), 2)
            ];
        })->values()->all();

        // Details per employee if needed for the view
        $employeesDeductions = $transactions->groupBy('employee_id')->map(function ($employeeTransactions, $employeeId) {
            $employeeName = $employeeTransactions->first()->employee?->name ?? 'Unknown';

            $employeeMonthly = $employeeTransactions->groupBy(function (SalaryTransaction $tx) {
                return \Carbon\Carbon::parse($tx->date)->format('Y-m');
            })->sortKeys()->map(function ($monthTransactions, $monthKey) {
                $deductionsList = $monthTransactions->groupBy(function (SalaryTransaction $tx) {
                    return $tx->description ?: ucfirst(str_replace('_', ' ', $tx->sub_type ?? $tx->type));
                })->map(function ($group, $name) {
                    return [
                        'deduction_name' => $name,
                        'deduction_amount' => round(abs((float) $group->sum('amount')), 2)
                    ];
                })->values()->all();

                return [
                    'month' => $monthKey,
                    'month_name' => \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('F Y'),
                    'deductions_list' => $deductionsList,
                    'month_total' => round(abs((float) $monthTransactions->sum('amount')), 2)
                ];
            })->values()->all();

            return [
                'employee_id' => $employeeId,
                'employee_name' => $employeeName,
                'monthly_deductions' => $employeeMonthly,
                'total_deductions' => round(abs((float) $employeeTransactions->sum('amount')), 2)
            ];
        })->values()->all();

        $reportTitle = $this->resolveReportTitle($filters);

        return [
            'report_title' => $reportTitle,
            'from_date' => $filters->fromDate->format('Y-m-d'),
            'to_date' => $filters->toDate->format('Y-m-d'),
            'employee_id' => $filters->employeeId,
            'branch_id' => $filters->branchId,
            'monthly_deductions' => $monthlyDeductions,
            'employees_deductions' => $employeesDeductions,
            'grand_total' => round(abs((float) $transactions->sum('amount')), 2),
            'transactions' => $transactions, // Just in case detailed view is needed
        ];
    }

    /**
     * Generate an empty payload format.
     */
    private function emptyResponse(DeductionReportFilterDTO $filters): array
    {
        return [
            'report_title' => $this->resolveReportTitle($filters),
            'from_date' => $filters->fromDate->format('Y-m-d'),
            'to_date' => $filters->toDate->format('Y-m-d'),
            'employee_id' => $filters->employeeId,
            'branch_id' => $filters->branchId,
            'monthly_deductions' => [],
            'employees_deductions' => [],
            'grand_total' => 0.0,
            'transactions' => collect(),
        ];
    }

    /**
     * Resolve the readable title for the report based on filters.
     */
    private function resolveReportTitle(DeductionReportFilterDTO $filters): string
    {
        if ($filters->employeeId) {
            return Employee::find($filters->employeeId)?->name ?? 'Unknown Employee';
        }

        if ($filters->branchId) {
            return Branch::find($filters->branchId)?->name ?? 'Unknown Branch';
        }

        return __('All Employees');
    }
}
