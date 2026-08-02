<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class PayrollTransactionsExport implements FromView
{
    private $transactions;
    private string $employeeName;
    private float $netSalary;

    public function __construct($transactions, string $employeeName = '', float $netSalary = 0.0)
    {
        $this->transactions = $transactions;
        $this->employeeName = $employeeName;
        $this->netSalary = $netSalary;
    }

    public function view(): View
    {
        $transactions = $this->mergeSimilarTransactions(collect($this->transactions))->map(function ($tx) {
            if (str_contains($tx->description ?? '', 'Advance installment')) {
                $tx->description = 'Advance Installment';
            }
            return $tx;
        });

        return view('export.reports.hr.payrolls.payroll-transactions-excel', [
            'transactions' => $transactions,
            'employeeName' => $this->employeeName,
            'netSalary' => $this->netSalary,
        ]);
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
                $label = $this->mergeLabel($first);

                return (object) [
                    'id'          => $first->id,
                    'operation'   => $first->operation,
                    'type'        => $first->type,
                    'sub_type'    => $first->sub_type,
                    'description' => $label,
                    'amount'      => round((float) $group->sum('amount'), 2),
                    'status'      => $first->status,
                    'date'        => $first->date,
                    'unit'        => $first->unit,
                    'qty'         => $group->sum('qty'),
                    'rate'        => $first->rate,
                    'multiplier'  => $first->multiplier,
                ];
            })
            ->values();
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
