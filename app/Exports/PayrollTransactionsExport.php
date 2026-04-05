<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PayrollTransactionsExport implements FromView
{
    private $transactions;
    private string $employeeName;

    public function __construct($transactions, string $employeeName = '')
    {
        $this->transactions = $transactions;
        $this->employeeName = $employeeName;
    }

    public function view(): View
    {
        $transactions = $this->transactions->map(function ($tx) {
            if (str_contains($tx->description ?? '', 'Advance installment')) {
                $tx->description = 'Advance Installment';
            }
            return $tx;
        });

        return view('export.reports.hr.payrolls.payroll-transactions-excel', [
            'transactions' => $transactions,
            'employeeName' => $this->employeeName,
        ]);
    }
}
