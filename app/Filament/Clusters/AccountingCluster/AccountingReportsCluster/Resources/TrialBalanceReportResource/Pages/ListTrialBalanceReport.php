<?php

namespace App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\TrialBalanceReportResource\Pages;

use App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\TrialBalanceReportResource;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListTrialBalanceReport extends ListRecords
{
    protected static string $resource = TrialBalanceReportResource::class;

    protected static string $view = 'filament.pages.accounting-reports.trial-balance-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters()['date_range']->getState() ?? [];

        $startDate = $filters['start_date'] ?? now()->startOfYear();
        $endDate = $filters['end_date'] ?? now();

        $accounts = Account::with(['lines' => function ($query) use ($startDate, $endDate) {
            $query->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            });
        }])->get();

        $reportData = $accounts->map(function ($account) {
            $debit = $account->lines->sum('debit');
            $credit = $account->lines->sum('credit');

            return [
                'account_name' => $account->name,
                'account_code' => $account->code,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $debit - $credit,
            ];
        });

        return [
            'reportData' => $reportData,
            'dateRange' => ['from' => $startDate, 'to' => $endDate],
        ];
    }
}
