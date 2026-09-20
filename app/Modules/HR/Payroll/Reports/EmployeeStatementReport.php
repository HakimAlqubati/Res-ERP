<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Reports;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Employee;
use App\Models\SalaryTransaction;
use App\Modules\HR\Payroll\DTOs\EmployeeStatementFilterDTO;
use Illuminate\Support\Collection;

/**
 * Employee Payroll Transactions Statement Report Service.
 *
 * Provides comprehensive tracking and financial aggregation of an employee's
 * salary transactions over a given date range.
 */
class EmployeeStatementReport
{
    /**
     * Generate statement report data based on the provided filter DTO.
     *
     * @param EmployeeStatementFilterDTO $filters
     * @return array<string, mixed>
     */
    public function getReport(EmployeeStatementFilterDTO $filters): array
    {
        if (! $filters->hasEmployee()) {
            return $this->emptyResponse($filters);
        }

        /** @var Employee|null $employee */
        $employee = Employee::with(['branch', 'department'])->find($filters->employeeId);

        if (! $employee) {
            return $this->emptyResponse($filters);
        }

        $query = SalaryTransaction::query()
            ->where('employee_id', $filters->employeeId)
            ->whereDate('date', '>=', $filters->fromDate->format('Y-m-d'))
            ->whereDate('date', '<=', $filters->toDate->format('Y-m-d'));

        if (! empty($filters->status)) {
            $query->where('status', $filters->status);
        }

        /** @var Collection<int, SalaryTransaction> $transactions */
        $transactions = $query->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $currency = $transactions->first()?->currency ?: ($employee->currency ?: SalaryTransaction::defaultCurrency());

        $totalAdditions = (float) $transactions->sum(function (SalaryTransaction $tx) {
            $typeVal = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;

            // Exclude Employer Contribution from employee earnings/additions
            if ($typeVal === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value) {
                return 0;
            }

            return $tx->operation === '+' ? (float) $tx->amount : 0;
        });

        $totalDeductions = (float) $transactions->sum(function (SalaryTransaction $tx) {
            $typeVal = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;

            // Exclude Employer Contribution & Carry Forward deficit from deductions
            if (
                $typeVal === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value ||
                $typeVal === SalaryTransactionType::TYPE_CARRY_FORWARD->value
            ) {
                return 0;
            }

            return $tx->operation === '-' ? (float) $tx->amount : 0;
        });

        $finalResult = $totalAdditions - $totalDeductions;
        $displayDateFormat = function_exists('settingWithDefault') ? settingWithDefault('date_format', 'Y-m-d') : 'Y-m-d';

        $formattedTransactions = $transactions->values()->map(function (SalaryTransaction $tx, int $index) use ($currency, $displayDateFormat) {
            $typeVal = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;
            $subTypeVal = $tx->sub_type instanceof \BackedEnum ? $tx->sub_type->value : (string) ($tx->sub_type ?? '');

            return [
                'index'                    => $index + 1,
                'id'                       => $tx->id,
                'type'                     => ucfirst(str_replace('_', ' ', $typeVal)),
                'sub_type'                 => ! empty($subTypeVal) ? ucfirst(str_replace('_', ' ', $subTypeVal)) : '',
                'operation'                => $tx->operation === '-' ? '-' : '+',
                'amount'                   => formatMoneyWithCurrency($tx->amount, $currency),
                'raw_amount'               => (float) $tx->amount,
                'date'                     => $tx->date ? \Carbon\Carbon::parse($tx->date)->format($displayDateFormat) : '',
                'description'              => $tx->description ?: ($tx->notes ?: '-'),
                'is_employer_contribution' => $typeVal === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value,
            ];
        });

        return [
            'has_data'             => true,
            'employee'             => $employee,
            'employee_id'          => $employee->id,
            'employee_name'        => $employee->name,
            'employee_code'        => $employee->id,
            'branch_name'          => $employee->branch?->name,
            'avatar_image'         => $employee->avatar_image,
            'period_label'         => $filters->getFormattedPeriod(),
            'from_date'            => $filters->fromDate->format($displayDateFormat),
            'to_date'              => $filters->toDate->format($displayDateFormat),
            'transactions'         => $formattedTransactions,
            'total_additions'      => formatMoneyWithCurrency($totalAdditions, $currency),
            'total_deductions'     => formatMoneyWithCurrency($totalDeductions, $currency),
            'final_result'         => formatMoneyWithCurrency($finalResult, $currency),
            'raw_total_additions'  => round($totalAdditions, 2),
            'raw_total_deductions' => round($totalDeductions, 2),
            'raw_final_result'     => round($finalResult, 2),
            'currency'             => $currency,
        ];
    }

    /**
     * Provide an empty response structure when no data or employee is specified.
     *
     * @param EmployeeStatementFilterDTO $filters
     * @return array<string, mixed>
     */
    protected function emptyResponse(EmployeeStatementFilterDTO $filters): array
    {
        $displayDateFormat = function_exists('settingWithDefault') ? settingWithDefault('date_format', 'Y-m-d') : 'Y-m-d';

        return [
            'has_data'             => false,
            'employee'             => null,
            'employee_id'          => null,
            'employee_name'        => null,
            'employee_code'        => null,
            'branch_name'          => null,
            'avatar_image'         => null,
            'period_label'         => $filters->getFormattedPeriod(),
            'from_date'            => $filters->fromDate->format($displayDateFormat),
            'to_date'              => $filters->toDate->format($displayDateFormat),
            'transactions'         => collect(),
            'total_additions'      => formatMoneyWithCurrency(0),
            'total_deductions'     => formatMoneyWithCurrency(0),
            'final_result'         => formatMoneyWithCurrency(0),
            'raw_total_additions'  => 0.0,
            'raw_total_deductions' => 0.0,
            'raw_final_result'     => 0.0,
            'currency'             => SalaryTransaction::defaultCurrency(),
        ];
    }
}
