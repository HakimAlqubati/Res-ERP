<?php

namespace App\Services\Accounting;

use App\DTOs\Accounting\TrialBalanceRequestDTO;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    /**
     * Generate Trial Balance Report
     */
    public function getTrialBalance(TrialBalanceRequestDTO $dto): array
    {
        $query = Account::query()
            ->select([
                'acc_accounts.id',
                'acc_accounts.account_code',
                'acc_accounts.account_name',
                'acc_accounts.account_type',
                DB::raw('COALESCE(SUM(acc_journal_entry_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(acc_journal_entry_lines.credit), 0) as total_credit'),
            ])
            ->leftJoin('acc_journal_entry_lines', 'acc_accounts.id', '=', 'acc_journal_entry_lines.account_id')
            ->leftJoin('acc_journal_entries', 'acc_journal_entry_lines.journal_entry_id', '=', 'acc_journal_entries.id')
            ->whereNull('acc_accounts.deleted_at')
            ->where(function ($q) use ($dto) {
                $q->whereNull('acc_journal_entries.id')
                    ->orWhere(function ($subQ) use ($dto) {
                        $subQ->where('acc_journal_entries.status', 'posted')
                            ->whereBetween('acc_journal_entries.entry_date', [$dto->startDate, $dto->endDate])
                            ->whereNull('acc_journal_entries.deleted_at');
                    });
            });

        // Filter by account type if specified
        if ($dto->accountType) {
            $query->where('acc_accounts.account_type', $dto->accountType);
        }

        $query->groupBy([
            'acc_accounts.id',
            'acc_accounts.account_code',
            'acc_accounts.account_name',
            'acc_accounts.account_type',
        ]);

        // Filter zero balances if needed
        if (!$dto->showZeroBalances) {
            $query->havingRaw('(COALESCE(SUM(acc_journal_entry_lines.debit), 0) + COALESCE(SUM(acc_journal_entry_lines.credit), 0)) > 0');
        }

        $query->orderBy('acc_accounts.account_code');

        $accounts = $query->get()->map(function ($account) {
            $debit = (float) $account->total_debit;
            $credit = (float) $account->total_credit;
            $balance = $debit - $credit;

            return [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => abs($balance),
                'balance_type' => $balance >= 0 ? 'debit' : 'credit',
                'debit_formatted' => number_format($debit, 2),
                'credit_formatted' => number_format($credit, 2),
                'balance_formatted' => number_format(abs($balance), 2),
            ];
        });

        // Calculate totals
        $totalDebit = $accounts->sum('debit');
        $totalCredit = $accounts->sum('credit');
        $difference = abs($totalDebit - $totalCredit);

        return [
            'accounts' => $accounts->toArray(),
            'totals' => [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'difference' => $difference,
                'is_balanced' => $difference < 0.01, // Allow for rounding errors
                'total_debit_formatted' => number_format($totalDebit, 2),
                'total_credit_formatted' => number_format($totalCredit, 2),
                'difference_formatted' => number_format($difference, 2),
            ],
            'summary' => [
                'account_count' => $accounts->count(),
                'start_date' => $dto->startDate,
                'end_date' => $dto->endDate,
            ],
        ];
    }
}
