<?php

namespace App\Modules\HR\Payroll\Reports;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Payroll;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class SalarySlipReport
{
    /**
     * Generate and download the Salary Slip PDF.
     * 
     * @param int|string $payrollId
     * @return \Illuminate\Http\Response
     */
    public function generate($payrollId)
    {
        /** @var \App\Models\Payroll $payroll */
        $payroll = Payroll::with([
            'employee',
            'employee.department',
            'employee.position',
            'transactions',
        ])->findOrFail($payrollId);

        // Sort transactions by date
        $transactions = $payroll->transactions()->orderBy('date')->get();

        // Split transactions
        $earnings = $transactions->filter(fn($t) => $t->operation === '+');
        $deductions = $transactions->filter(fn($t) => $t->operation === '-');

        // Employer contributions (for display only)
        $employerContrib = $transactions->filter(fn($t) => $t->type === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value);

        // Totals
        $gross = $earnings->sum('amount');

        // Exclude Carry Forward from the TOTAL sum, but keep them in the $deductions list for display
        $totalDeductions = $deductions->filter(function ($t) {
            return $t->type !== SalaryTransactionType::TYPE_CARRY_FORWARD->value;
        })->sum('amount');

        $net = max($gross - $totalDeductions, 0);
        $totalEmployer = $employerContrib->sum('amount');

        // Helper for words (placeholder)
        $amountInWords = function (float $value) {
            if (function_exists('number_to_words')) {
                // return number_to_words($value);
                return '';
            }
            return '';
        };

        $data = [
            'payroll'         => $payroll,
            'transactions'    => $transactions,
            'earnings'        => $earnings,
            'deductions'      => $deductions,
            'employerContrib' => $employerContrib,
            'gross'           => $gross,
            'totalDeductions' => $totalDeductions,
            'net'             => $net,
            'totalEmployer'   => $totalEmployer,
            'amountInWords'   => $amountInWords($net),
        ];

        $pdf = LaravelMpdf::loadView('reports.hr.payroll.salary-slip-pdf', $data);

        $filename = sprintf(
            'SalarySlip-%s-%s-%s.pdf',
            $payroll->employee?->name ?? '000',
            $payroll->year,
            $payroll->month
        );

        // Use streamDownload for Livewire compatibility
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
