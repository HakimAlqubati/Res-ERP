<?php

namespace App\Modules\HR\Payroll\Reports;

use App\Models\Payroll;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;
use App\Enums\HR\Payroll\SalaryTransactionType;

class TransactionsReport
{
    /**
     * Generate and download the Payroll Transactions PDF.
     * 
     * @param int|string $payrollId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function generate($payrollId)
    {
        /** @var \App\Models\Payroll $payroll */
        $payroll = Payroll::with([
            'employee',
            'transactions'
        ])->findOrFail($payrollId);

        $transactions = $payroll->transactions()->orderBy('date')->get();

        $total = $transactions->sum(function ($t) {
            if (isset($t->type) && $t->type === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value) {
                return 0;
            }
            return $t->operation === '+' ? $t->amount : -$t->amount;
        });

        $totalDeductions = $transactions->sum(function ($t) {
            if (isset($t->type) && $t->type === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value) {
                return 0;
            }
            return $t->operation === '-' ? $t->amount : 0;
        });

        $totalAdditions = $transactions->sum(function ($t) {
            if (isset($t->type) && $t->type === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value) {
                return 0;
            }
            return $t->operation === '+' ? $t->amount : 0;
        });

        $total = $total >= 0 ? $total : 0; // Or keep negative? Original logic had formatMoneyWithCurrency($total)
        $formattedTotal = formatMoneyWithCurrency($total);

        $data = [
            'payroll'         => $payroll,
            'transactions'    => $transactions,
            'total'           => $formattedTotal,
            'totalDeductions' => formatMoneyWithCurrency($totalDeductions),
            'totalAdditions'  => formatMoneyWithCurrency($totalAdditions),
        ];

        $pdf = LaravelMpdf::loadView('reports.hr.payroll.transactions-pdf', $data, [], [
            'format' => 'A4',
            'orientation' => 'P'
        ]);

        $filename = sprintf(
            'Transactions-%s-%s-%s.pdf',
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
