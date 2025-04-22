<?php

namespace App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\GeneralLedgerReportResource\Pages;

use App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\GeneralLedgerReportResource;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Filament\Resources\Pages\ListRecords;

class ListGeneralLedgerReport extends ListRecords
{
    protected static string $resource = GeneralLedgerReportResource::class;

    protected static string $view = 'filament.pages.accounting-reports.general-ledger-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters()['filters']->getState() ?? [];

        $accountId = $filters['account_id'] ?? null;
        $startDate = $filters['start_date'] ?? now()->startOfYear();
        $endDate = $filters['end_date'] ?? now();

        $account = Account::find($accountId);
        $lines = [];

        if ($accountId) {
            $lines = \App\Models\JournalEntryLine::query()
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('account_id', $accountId)
                ->whereBetween('journal_entries.date', [$startDate, $endDate])
                ->orderBy('journal_entries.date', 'asc')
                ->select('journal_entry_lines.*', 'journal_entries.date as entry_date', 'journal_entries.description')
                ->with('journalEntry')
                ->get()
                ->map(function ($line) {
                    return [
                        'date' => $line->entry_date,
                        'description' => $line->description,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                    ];
                });
        }

        return [
            'account' => $account,
            'entries' => $lines,
            'dateRange' => ['from' => $startDate, 'to' => $endDate],
        ];
    }
}
