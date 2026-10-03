<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Reports;

use App\Enums\HR\Payroll\SalaryTransactionSubType;
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
        $transactions = $query->with(['payroll'])
            ->orderBy('date', 'asc')
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

        // Employer Contributions are company-borne costs (informational only, no impact on employee balance)
        $totalEmployerContributions = (float) $transactions->sum(function (SalaryTransaction $tx) {
            $typeVal = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;

            return $typeVal === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value ? (float) $tx->amount : 0;
        });

        $finalResult = $totalAdditions - $totalDeductions;
        $displayDateFormat = (function_exists('settingWithDefault') ? settingWithDefault('date_format', 'Y-m-d') : null) ?: 'Y-m-d';

        $runningBalance = 0.0;
        $totalPaidAdditions = 0.0;
        $totalPaidDeductions = 0.0;
        $totalPaymentsMade = 0.0;

        // Group transactions by month (e.g. YYYY-MM) to allow synthesizing a Payment transaction at the end of each paid month/payroll
        $groupedByMonth = $transactions->groupBy(function (SalaryTransaction $tx) {
            return $tx->date
                ? \Carbon\Carbon::parse($tx->date)->format('Y-m')
                : ($tx->year && $tx->month ? sprintf('%04d-%02d', $tx->year, $tx->month) : 'general');
        });

        $formattedTransactions = collect();
        $rowIndex = 1;

        foreach ($groupedByMonth as $monthKey => $monthTransactions) {
            $monthAdditions = 0.0;
            $monthDeductions = 0.0;
            $monthPayroll = null;
            $isMonthPaid = false;
            $lastTxDate = null;
            $lastTxDateRaw = null;

            foreach ($monthTransactions as $tx) {
                $typeVal = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;
                $subTypeVal = $tx->sub_type instanceof \BackedEnum ? $tx->sub_type->value : (string) ($tx->sub_type ?? '');
                $isEmployerContribution = $typeVal === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value;
                $isCarryForward = $typeVal === SalaryTransactionType::TYPE_CARRY_FORWARD->value;

                if ($tx->payroll) {
                    $monthPayroll = $tx->payroll;
                    if ($tx->payroll->is_paid || $tx->payroll->status === 'paid' || ! empty($tx->payroll->paid_at)) {
                        $isMonthPaid = true;
                    }
                }

                if (! $isEmployerContribution) {
                    if ($tx->operation === '+') {
                        $runningBalance += (float) $tx->amount;
                        $monthAdditions += (float) $tx->amount;
                        if ($isMonthPaid) {
                            $totalPaidAdditions += (float) $tx->amount;
                        }
                    } elseif ($tx->operation === '-' && ! $isCarryForward) {
                        $runningBalance -= (float) $tx->amount;
                        $monthDeductions += (float) $tx->amount;
                        if ($isMonthPaid) {
                            $totalPaidDeductions += (float) $tx->amount;
                        }
                    }
                }

                if ($tx->date) {
                    $lastTxDateRaw = $tx->date;
                    $lastTxDate = \Carbon\Carbon::parse($tx->date)->format($displayDateFormat ?: 'Y-m-d');
                }

                $formattedTransactions->push([
                    'index'                    => $rowIndex++,
                    'id'                       => $tx->id,
                    'type'                     => $this->resolveDisplayType($typeVal, $subTypeVal, $tx->description ?: ($tx->notes ?: '')),
                    'sub_type'                 => ! empty($subTypeVal) ? ucfirst(str_replace('_', ' ', $subTypeVal)) : '',
                    'operation'                => $isEmployerContribution ? 'info' : ($tx->operation === '-' ? '-' : '+'),
                    'amount'                   => formatMoneyWithCurrency($tx->amount, $currency),
                    'raw_amount'               => (float) $tx->amount,
                    'payment'                  => '—',
                    'paid'                     => '—',
                    'raw_paid'                 => 0.0,
                    'is_paid'                  => false,
                    'balance'                  => formatMoneyWithCurrency($runningBalance, $currency),
                    'raw_balance'              => round($runningBalance, 2),
                    'date'                     => $tx->date ? \Carbon\Carbon::parse($tx->date)->format($displayDateFormat ?: 'Y-m-d') : '',
                    'description'              => $tx->description ?: ($tx->notes ?: ''),
                    'is_employer_contribution' => $isEmployerContribution,
                ]);
            }

            // Fallback: check if a Payroll record exists in database for this employee and month
            if (! $monthPayroll && $filters->hasEmployee() && strlen((string) $monthKey) === 7) {
                $monthPayroll = \App\Models\Payroll::where('employee_id', $filters->employeeId)
                    ->where('year', (int) substr($monthKey, 0, 4))
                    ->where('month', (int) substr($monthKey, 5, 2))
                    ->first();
                if ($monthPayroll && ($monthPayroll->is_paid || $monthPayroll->status === 'paid' || ! empty($monthPayroll->paid_at))) {
                    $isMonthPaid = true;
                }
            }

            // Synthesize Payment transaction if this month's payroll was paid
            if ($isMonthPaid) {
                $monthNetPaid = max(0.0, $monthAdditions - $monthDeductions);
                if ($monthPayroll && (float) $monthPayroll->net_salary > 0) {
                    $monthNetPaid = (float) $monthPayroll->net_salary;
                }

                if ($monthNetPaid > 0) {
                    $runningBalance = max(0.0, round($runningBalance - $monthNetPaid, 2));
                    $totalPaymentsMade += $monthNetPaid;

                    // Payment date: Prioritize paid_at from the Payroll model, then payment_date, or end of payroll month
                    $paymentDateObj = null;
                    if ($monthPayroll && ! empty($monthPayroll->paid_at)) {
                        $paymentDateObj = \Carbon\Carbon::parse($monthPayroll->paid_at);
                    } elseif ($monthPayroll && ! empty($monthPayroll->payment_date)) {
                        $paymentDateObj = \Carbon\Carbon::parse($monthPayroll->payment_date);
                    } elseif ($monthPayroll && ! empty($monthPayroll->year) && ! empty($monthPayroll->month)) {
                        $paymentDateObj = \Carbon\Carbon::createFromDate((int) $monthPayroll->year, (int) $monthPayroll->month, 1)->endOfMonth();
                    } elseif ($monthPayroll && ! empty($monthPayroll->period_end_date)) {
                        $paymentDateObj = \Carbon\Carbon::parse($monthPayroll->period_end_date)->endOfMonth();
                    } elseif (preg_match('/^\d{4}-\d{2}$/', (string) $monthKey)) {
                        $paymentDateObj = \Carbon\Carbon::createFromFormat('Y-m', (string) $monthKey)->endOfMonth();
                    } elseif ($lastTxDateRaw) {
                        $paymentDateObj = \Carbon\Carbon::parse($lastTxDateRaw)->endOfMonth();
                    } else {
                        $paymentDateObj = $filters->toDate ? $filters->toDate->copy()->endOfMonth() : \Carbon\Carbon::now()->endOfMonth();
                    }

                    $paymentDateFormatted = $paymentDateObj->format($displayDateFormat ?: 'Y-m-d');

                    $formattedTransactions->push([
                        'index'                    => $rowIndex++,
                        'id'                       => null,
                        'type'                     => 'Salary Payout',
                        'sub_type'                 => 'salary_payment',
                        'operation'                => 'info',
                        'amount'                   => '—',
                        'raw_amount'               => 0.0,
                        'payment'                  => formatMoneyWithCurrency($monthNetPaid, $currency),
                        'paid'                     => formatMoneyWithCurrency($monthNetPaid, $currency),
                        'raw_paid'                 => $monthNetPaid,
                        'is_paid'                  => true,
                        'balance'                  => formatMoneyWithCurrency($runningBalance, $currency),
                        'raw_balance'              => round($runningBalance, 2),
                        'date'                     => $paymentDateFormatted,
                        'description'              => __('Salary Payment'),
                        'is_employer_contribution' => false,
                    ]);
                }
            }
        }

        $totalPaid = $totalPaymentsMade > 0 ? $totalPaymentsMade : max(0.0, $totalPaidAdditions - $totalPaidDeductions);
        $remainingBalance = max(0.0, $finalResult - $totalPaid);

        return [
            'has_data'              => true,
            'employee'              => $employee,
            'employee_id'           => $employee->id,
            'employee_name'         => $employee->name,
            'employee_code'         => $employee->id,
            'branch_name'           => $employee->branch?->name,
            'avatar_image'          => $employee->avatar_image,
            'period_label'          => $filters->getFormattedPeriod(),
            'from_date'             => $filters->fromDate->format($displayDateFormat ?: 'Y-m-d'),
            'to_date'               => $filters->toDate->format($displayDateFormat ?: 'Y-m-d'),
            'transactions'          => $formattedTransactions,
            'total_additions'       => formatMoneyWithCurrency($totalAdditions, $currency),
            'total_deductions'      => formatMoneyWithCurrency($totalDeductions, $currency),
            'final_result'          => formatMoneyWithCurrency($finalResult, $currency),
            'total_paid'            => formatMoneyWithCurrency($totalPaid, $currency),
            'remaining_balance'     => formatMoneyWithCurrency($remainingBalance, $currency),
            'raw_total_additions'   => round($totalAdditions, 2),
            'raw_total_deductions'  => round($totalDeductions, 2),
            'raw_final_result'      => round($finalResult, 2),
            'raw_total_paid'        => round($totalPaid, 2),
            'raw_remaining_balance' => round($remainingBalance, 2),
            'total_employer_contributions'     => formatMoneyWithCurrency($totalEmployerContributions, $currency),
            'raw_total_employer_contributions' => round($totalEmployerContributions, 2),
            'currency'              => $currency,
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
        $displayDateFormat = (function_exists('settingWithDefault') ? settingWithDefault('date_format', 'Y-m-d') : null) ?: 'Y-m-d';

        return [
            'has_data'              => false,
            'employee'              => null,
            'employee_id'           => null,
            'employee_name'         => null,
            'employee_code'         => null,
            'branch_name'           => null,
            'avatar_image'          => null,
            'period_label'          => $filters->getFormattedPeriod(),
            'from_date'             => $filters->fromDate->format($displayDateFormat ?: 'Y-m-d'),
            'to_date'               => $filters->toDate->format($displayDateFormat ?: 'Y-m-d'),
            'transactions'          => collect(),
            'total_additions'       => formatMoneyWithCurrency(0),
            'total_deductions'      => formatMoneyWithCurrency(0),
            'final_result'          => formatMoneyWithCurrency(0),
            'total_paid'            => formatMoneyWithCurrency(0),
            'remaining_balance'     => formatMoneyWithCurrency(0),
            'raw_total_additions'   => 0.0,
            'raw_total_deductions'  => 0.0,
            'raw_final_result'      => 0.0,
            'raw_total_paid'        => 0.0,
            'raw_remaining_balance' => 0.0,
            'total_employer_contributions'     => formatMoneyWithCurrency(0),
            'raw_total_employer_contributions' => 0.0,
            'currency'              => SalaryTransaction::defaultCurrency(),
        ];
    }

    /**
     * Map raw transaction type/sub_type values to the required display labels.
     *
     * Display types required:
     *  - Basic Salary
     *  - Allowance
     *  - Overtime
     *  - Bonus
     *  - Deduction
     *  - Salary advance recovery
     *  - Expense advance recovery
     *  - Salary Payout
     */
    protected function resolveDisplayType(string $typeVal, string $subTypeVal, string $description = ''): string
    {
        // Map by main type value
        $typeMap = [
            SalaryTransactionType::TYPE_SALARY->value              => 'Basic Salary',
            SalaryTransactionType::TYPE_ALLOWANCE->value           => 'Allowance',
            SalaryTransactionType::TYPE_OVERTIME->value            => 'Bonus',
            SalaryTransactionType::TYPE_BONUS->value               => 'Bonus',
            SalaryTransactionType::TYPE_DEDUCTION->value           => 'Deduction',
            SalaryTransactionType::TYPE_PENALTY->value             => 'Deduction',
            SalaryTransactionType::TYPE_ADVANCE->value             => 'Salary advance recovery',
            SalaryTransactionType::TYPE_INSTALL->value             => 'Salary advance recovery',
            SalaryTransactionType::TYPE_ADVANCE_WAGE->value        => 'Expense advance recovery',
            SalaryTransactionType::TYPE_NET_SALARY->value          => 'Salary Payout',
            SalaryTransactionType::TYPE_ADJUSTMENT->value          => 'Adjustment',
            SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value => 'Employer Contribution',
            SalaryTransactionType::TYPE_CARRY_FORWARD->value       => 'Carry Forward',
            SalaryTransactionType::TYPE_OTHER->value               => 'Other',
        ];

        // Sub-type overrides: e.g. advance_installment sub_type should show as recovery
        $subTypeOverrides = [
            SalaryTransactionSubType::ADVANCE_INSTALLMENT->value       => 'Salary advance recovery',
            SalaryTransactionSubType::EARLY_ADVANCE_INSTALLMENT->value => 'Salary advance recovery',
            SalaryTransactionSubType::OVERTIME->value                  => 'Bonus',
            SalaryTransactionSubType::OVERTIME_DAYS->value             => 'Bonus',
            SalaryTransactionSubType::BASE_SALARY->value               => 'Basic Salary',
            SalaryTransactionSubType::ADVANCE_WAGE->value              => 'Expense advance recovery',
        ];

        // Check sub-type overrides first for more specific labelling
        if (! empty($subTypeVal) && isset($subTypeOverrides[$subTypeVal])) {
            return $subTypeOverrides[$subTypeVal];
        }

        // Fallback: detect overtime from description when sub_type is missing
        if ($typeVal === SalaryTransactionType::TYPE_ALLOWANCE->value
            && stripos($description, 'overtime') !== false) {
            return 'Bonus';
        }

        return $typeMap[$typeVal] ?? ucfirst(str_replace('_', ' ', $typeVal));
    }
}
