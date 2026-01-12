<?php

namespace App\Services\Accounting;

use App\DTOs\Accounting\AccountingIncomeStatementRequestDTO;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class AccountingIncomeStatementService
{
    /**
     * Generate Accounting Income Statement Report
     */
    public function getIncomeStatement(AccountingIncomeStatementRequestDTO $dto): array
    {
        // 1. Get Revenue Accounts & Totals
        $revenueData = $this->getAccountGroupData($dto, Account::TYPE_REVENUE);

        // 2. Get Expense Accounts & Totals
        $expenseData = $this->getAccountGroupData($dto, Account::TYPE_EXPENSE);

        $totalRevenue = $revenueData['total'];
        $totalExpense = $expenseData['total'];
        $netProfit = $totalRevenue - $totalExpense;
        $profitRatio = ($totalRevenue > 0) ? ($netProfit / $totalRevenue) * 100 : 0;

        return [
            'revenue' => [
                'details' => $revenueData['accounts'],
                'total' => $totalRevenue,
                'total_formatted' => formatMoneyWithCurrency($totalRevenue),
            ],
            'expenses' => [
                'details' => $expenseData['accounts'],
                'total' => $totalExpense,
                'total_formatted' => formatMoneyWithCurrency($totalExpense),
            ],
            'gross_profit' => [
                'value' => $netProfit,
                'value_formatted' => formatMoneyWithCurrency($netProfit),
                'is_profit' => $netProfit >= 0,
                'ratio' => $profitRatio,
                'ratio_formatted' => number_format($profitRatio, 2) . '%',
            ],
            'summary' => [

                'start_date' => $dto->startDate,
                'end_date' => $dto->endDate,
                'branch_id' => $dto->branchId,
            ]
        ];
    }

    /**
     * Helper to get data for a specific account type
     */
    private function getAccountGroupData(AccountingIncomeStatementRequestDTO $dto, string $type): array
    {
        $query = Account::query()
            ->select([
                'acc_accounts.id',
                'acc_accounts.account_code',
                'acc_accounts.account_name',
                DB::raw('COALESCE(SUM(acc_journal_entry_lines.credit) - SUM(acc_journal_entry_lines.debit), 0) as balance')
            ])
            ->join('acc_journal_entry_lines', 'acc_accounts.id', '=', 'acc_journal_entry_lines.account_id')
            ->join('acc_journal_entries', 'acc_journal_entry_lines.journal_entry_id', '=', 'acc_journal_entries.id')
            ->where('acc_accounts.account_type', $type)
            ->where('acc_journal_entries.status', 'posted')
            ->whereBetween('acc_journal_entries.entry_date', [$dto->startDate, $dto->endDate])
            ->whereNull('acc_journal_entries.deleted_at')
            ->whereNull('acc_accounts.deleted_at');

        if ($dto->branchId) {
            $query->where('acc_journal_entry_lines.branch_id', $dto->branchId);
        }

        $accounts = $query->groupBy('acc_accounts.id', 'acc_accounts.account_code', 'acc_accounts.account_name')
            ->get()
            ->map(function ($account) use ($type) {
                // For Revenue, Credit - Debit = Positive. For Expenses, Debit - Credit = Positive.
                $balance = (float) $account->balance;
                if ($type === Account::TYPE_EXPENSE) {
                    $balance = -$balance; // Invert for expenses (Debit - Credit)
                }

                return [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'amount' => $balance,
                    'amount_formatted' => formatMoneyWithCurrency($balance),
                ];
            })
            ->filter(fn($acc) => abs($acc['amount']) > 0)
            ->values();

        return [
            'accounts' => $accounts->toArray(),
            'total' => $accounts->sum('amount'),
        ];
    }
}
