<?php

namespace App\Exports;

use App\Enums\HR\Payroll\SalaryTransactionType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class PayrollsExport implements FromView
{
    private $payrolls;

    public function __construct($payrolls)
    {
        $this->payrolls = $payrolls;
    }

    public function view(): View
    {
        $this->payrolls->load('transactions', 'employee');
        $payrollGroups = $this->groupPayrollsByEmployee($this->payrolls);

        $additionColumns = collect();
        $deductionColumns = collect();
        $employerContributionColumns = collect();

        foreach ($payrollGroups as $group) {
            foreach ($group['transactions'] as $transaction) {
                $typeVal = $transaction->type instanceof \BackedEnum ? $transaction->type->value : $transaction->type;

                if ($typeVal === SalaryTransactionType::TYPE_SALARY->value) {
                    continue;
                }

                if ($typeVal === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value) {
                    $employerContributionColumns->push($this->mergeLabel($transaction));
                    continue;
                }

                if ($typeVal === SalaryTransactionType::TYPE_ADVANCE_WAGE->value) {
                    continue;
                }

                if ($typeVal === SalaryTransactionType::TYPE_ADVANCE->value) {
                    continue;
                }

                $columnName = $this->normalizedColumnName($transaction);
                if (empty($columnName)) {
                    continue;
                }

                if ($transaction->operation === '+') {
                    $additionColumns->push($columnName);
                } elseif ($transaction->operation === '-' && $typeVal !== SalaryTransactionType::TYPE_CARRY_FORWARD->value) {
                    $deductionColumns->push($columnName);
                }
            }
        }

        $additionHeaders = $additionColumns->unique()->filter()->values();
        $deductionHeaders = $deductionColumns->unique()->filter()->values();
        $employerContributionHeaders = $employerContributionColumns->unique()->filter()->values();

        $totals = [
            'base_salary'            => 0,
            'total_additions'        => 0,
            'gross_salary'           => 0,
            'total_deductions'       => 0,
            'employer_contributions' => [],
            'advance'                => 0,
            'advance_wages'          => 0,
            'net_salary'             => 0,
            'additions'              => [],
            'deductions'             => [],
        ];

        foreach ($additionHeaders as $col) {
            $totals['additions'][$col] = 0;
        }
        foreach ($deductionHeaders as $col) {
            $totals['deductions'][$col] = 0;
        }
        foreach ($employerContributionHeaders as $col) {
            $totals['employer_contributions'][$col] = 0;
        }

        $rows = [];
        foreach ($payrollGroups as $group) {
            $payroll = $group['payroll'];

            $row = [
                'employee_no'            => $payroll->employee?->employee_no,
                'employee_name'          => $payroll->employee?->name,
                'branch_name'            => $payroll->period_branch_name ?? '-',
                'base_salary'            => $group['base_salary'],
                'net_salary'             => $group['net_salary'],
                'employer_contribution'  => 0,
                'employer_contributions' => [],
                'advance'                => 0,
                'advance_wages'          => 0,
                'additions'              => [],
                'total_additions'        => 0,
                'deductions'             => [],
                'total_deductions'       => 0,
            ];

            foreach ($additionHeaders as $col) {
                $row['additions'][$col] = 0;
            }
            foreach ($deductionHeaders as $col) {
                $row['deductions'][$col] = 0;
            }
            foreach ($employerContributionHeaders as $col) {
                $row['employer_contributions'][$col] = 0;
            }

            foreach ($group['transactions'] as $transaction) {
                $typeVal = $transaction->type instanceof \BackedEnum ? $transaction->type->value : $transaction->type;

                if ($typeVal === SalaryTransactionType::TYPE_SALARY->value) {
                    continue;
                }

                if ($typeVal === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value) {
                    $colName = $this->mergeLabel($transaction);
                    if (isset($row['employer_contributions'][$colName])) {
                        $row['employer_contributions'][$colName] += $transaction->amount;
                    }
                    continue;
                }

                if ($typeVal === SalaryTransactionType::TYPE_ADVANCE_WAGE->value) {
                    $row['advance_wages'] += $transaction->amount;
                    continue;
                }

                if ($typeVal === SalaryTransactionType::TYPE_ADVANCE->value) {
                    $row['advance'] += $transaction->amount;
                    continue;
                }

                $columnName = $this->normalizedColumnName($transaction);

                if ($transaction->operation === '+') {
                    if (isset($row['additions'][$columnName])) {
                        $row['additions'][$columnName] += $transaction->amount;
                    }
                    $row['total_additions'] += $transaction->amount;
                } elseif ($transaction->operation === '-' && $typeVal !== SalaryTransactionType::TYPE_CARRY_FORWARD->value) {
                    if (isset($row['deductions'][$columnName])) {
                        $row['deductions'][$columnName] += $transaction->amount;
                    }
                    $row['total_deductions'] += $transaction->amount;
                }
            }

            $row['gross_salary'] = $row['base_salary'] + $row['total_additions'];

            $totals['base_salary'] += $row['base_salary'] ?? 0;
            $totals['net_salary'] += $row['net_salary'] ?? 0;
            $totals['advance'] += $row['advance'] ?? 0;
            $totals['advance_wages'] += $row['advance_wages'] ?? 0;
            $totals['total_additions'] += $row['total_additions'] ?? 0;
            $totals['gross_salary'] += $row['gross_salary'] ?? 0;
            $totals['total_deductions'] += $row['total_deductions'] ?? 0;

            foreach ($additionHeaders as $col) {
                $totals['additions'][$col] += $row['additions'][$col] ?? 0;
            }
            foreach ($deductionHeaders as $col) {
                $totals['deductions'][$col] += $row['deductions'][$col] ?? 0;
            }
            foreach ($employerContributionHeaders as $col) {
                $totals['employer_contributions'][$col] += $row['employer_contributions'][$col] ?? 0;
            }

            $rows[] = $row;
        }

        return view('export.reports.hr.payrolls.payrolls-excel', [
            'additionColumns'             => $additionHeaders,
            'deductionColumns'            => $deductionHeaders,
            'employerContributionColumns' => $employerContributionHeaders,
            'rows'                        => $rows,
            'totals'                      => $totals,
        ]);
    }

    private function groupPayrollsByEmployee(Collection $payrolls): Collection
    {
        return $payrolls
            ->groupBy(fn($payroll) => implode('|', [$payroll->payroll_run_id, $payroll->employee_id]))
            ->map(function (Collection $group) {
                $payroll = $group->first();

                return [
                    'payroll'      => $payroll,
                    'base_salary'  => round((float) $group->sum('base_salary'), 2),
                    'net_salary'   => round((float) $group->sum(fn($item) => (float) $item->getRawOriginal('net_salary')), 2),
                    'transactions' => $this->mergeSimilarTransactions(
                        $group->flatMap(fn($payroll) => $payroll->transactions)
                    ),
                ];
            })
            ->values();
    }

    private function mergeSimilarTransactions(Collection $transactions): Collection
    {
        return $transactions
            ->groupBy(fn($transaction) => implode('|', [
                $transaction->operation,
                $transaction->type,
                $transaction->sub_type,
                $transaction->unit,
                $transaction->multiplier,
                $this->mergeLabel($transaction),
            ]))
            ->map(function (Collection $group) {
                $first = $group->first();

                return (object) [
                    'operation'   => $first->operation,
                    'type'        => $first->type,
                    'sub_type'    => $first->sub_type,
                    'description' => $this->mergeLabel($first),
                    'amount'      => round((float) $group->sum('amount'), 2),
                    'unit'        => $first->unit,
                    'multiplier'  => $first->multiplier,
                ];
            })
            ->values();
    }

    private function normalizedColumnName($transaction): string
    {
        $columnName = $this->mergeLabel($transaction);

        if (str_contains($columnName, 'Advance installment')) {
            return 'Advance Installment';
        }

        return $columnName;
    }

    private function mergeLabel($transaction): string
    {
        if ($transaction->sub_type === 'base_salary') {
            return 'Base salary';
        }

        $label = $transaction->description
            ?: ucfirst(str_replace('_', ' ', $transaction->sub_type ?? ($transaction->type ?? '')));

        return trim((string) preg_replace('/\s*\([^)]*\d+\s*days?[^)]*\)\s*/i', ' ', $label));
    }
}
