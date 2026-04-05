<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
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

        $additionColumns = collect();
        $deductionColumns = collect();

        // Pass 1: Gather all unique column headers
        foreach ($this->payrolls as $payroll) {
            foreach ($payroll->transactions as $transaction) {
                $typeVal = $transaction->type instanceof \BackedEnum ? $transaction->type->value : $transaction->type;

                // Exclude basic salary and employer contribution as they have their own fixed columns
                if (
                    $typeVal === \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_SALARY->value ||
                    $typeVal === \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value
                ) {
                    continue;
                }

                $columnName = $transaction->description ?: $typeVal;
                if (str_contains($columnName, 'Advance installment')) {
                    $columnName = 'Advance Installment';
                }

                if (empty($columnName)) continue;

                if ($transaction->operation === '+') {
                    $additionColumns->push($columnName);
                } elseif ($transaction->operation === '-') {
                    if ($typeVal !== \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_CARRY_FORWARD->value) {
                        $deductionColumns->push($columnName);
                    }
                }
            }
        }

        $additionHeaders = $additionColumns->unique()->filter()->values();
        $deductionHeaders = $deductionColumns->unique()->filter()->values();

        $totals = [
            'base_salary'             => 0,
            'total_additions'         => 0,
            'total_deductions'        => 0,
            'employer_contribution'   => 0,
            'net_salary'              => 0,
            'additions'               => [],
            'deductions'              => [],
        ];

        foreach ($additionHeaders as $col) {
            $totals['additions'][$col] = 0;
        }
        foreach ($deductionHeaders as $col) {
            $totals['deductions'][$col] = 0;
        }

        // Pass 2: Prepare rows
        $rows = [];
        foreach ($this->payrolls as $payroll) {
            $row = [
                'employee_no'           => $payroll->employee?->employee_no,
                'employee_name'         => $payroll->employee?->name,
                'base_salary'           => $payroll->base_salary,
                'net_salary'            => $payroll->net_salary,
                'employer_contribution' => 0,
                'additions'             => [],
                'total_additions'       => 0,
                'deductions'            => [],
                'total_deductions'      => 0,
            ];

            // Initialize dynamic columns with 0
            foreach ($additionHeaders as $col) {
                $row['additions'][$col] = 0;
            }
            foreach ($deductionHeaders as $col) {
                $row['deductions'][$col] = 0;
            }

            // Populate transaction data
            foreach ($payroll->transactions as $transaction) {
                $typeVal = $transaction->type instanceof \BackedEnum ? $transaction->type->value : $transaction->type;

                // Exclude basic salary as it has its own fixed column
                if ($typeVal === \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_SALARY->value) {
                    continue;
                }

                // Handle employer contribution separately
                if ($typeVal === \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value) {
                    $row['employer_contribution'] += $transaction->amount;
                    continue;
                }

                $columnName = $transaction->description ?: $typeVal;
                if (str_contains($columnName, 'Advance installment')) {
                    $columnName = 'Advance Installment';
                }

                if ($transaction->operation === '+') {
                    if (isset($row['additions'][$columnName])) {
                        $row['additions'][$columnName] += $transaction->amount;
                    }
                    $row['total_additions'] += $transaction->amount;
                } elseif ($transaction->operation === '-') {
                    if ($typeVal !== \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_CARRY_FORWARD->value) {
                        if (isset($row['deductions'][$columnName])) {
                            $row['deductions'][$columnName] += $transaction->amount;
                        }
                        $row['total_deductions'] += $transaction->amount;
                    }
                }
            }

            $totals['base_salary']           += $row['base_salary'] ?? 0;
            $totals['net_salary']            += $row['net_salary'] ?? 0;
            $totals['employer_contribution'] += $row['employer_contribution'] ?? 0;
            $totals['total_additions']       += $row['total_additions'] ?? 0;
            $totals['total_deductions']      += $row['total_deductions'] ?? 0;

            foreach ($additionHeaders as $col) {
                $totals['additions'][$col] += $row['additions'][$col] ?? 0;
            }
            foreach ($deductionHeaders as $col) {
                $totals['deductions'][$col] += $row['deductions'][$col] ?? 0;
            }

            $rows[] = $row;
        }

        return view('export.reports.hr.payrolls.payrolls-excel', [
            'additionColumns'  => $additionHeaders,
            'deductionColumns' => $deductionHeaders,
            'rows'             => $rows,
            'totals'           => $totals,
        ]);
    }
}
