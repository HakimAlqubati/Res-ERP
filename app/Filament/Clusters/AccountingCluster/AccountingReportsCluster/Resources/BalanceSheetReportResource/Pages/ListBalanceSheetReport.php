<?php

namespace App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\BalanceSheetReportResource\Pages;

use App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\BalanceSheetReportResource;
use App\Models\Account;
use Filament\Resources\Pages\ListRecords;

class ListBalanceSheetReport extends ListRecords
{
    protected static string $resource = BalanceSheetReportResource::class;

    protected static string $view = 'filament.pages.accounting-reports.balance-sheet-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters()['date_range']->getState() ?? [];

        $startDate = $filters['start_date'] ?? now()->startOfYear();
        $endDate = $filters['end_date'] ?? now();

        $types = [
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
        ];

        $reportData = [];

        foreach ($types as $type => $label) {
            $accounts = Account::where('type', $type)
                ->with(['lines' => function ($q) use ($startDate, $endDate) {
                    $q->whereHas('journalEntry', function ($q2) use ($startDate, $endDate) {
                        $q2->whereBetween('date', [$startDate, $endDate]);
                    });
                }])->get();

            $reportData[$label] = $accounts->map(function ($account) use ($type) {
                $total = $type === 'asset'
                    ? $account->lines->sum('debit') - $account->lines->sum('credit')
                    : $account->lines->sum('credit') - $account->lines->sum('debit');

                return [
                    'name' => $account->name,
                    'code' => $account->code,
                    'balance' => $total,
                ];
            });
        }

        return [
            'reportData' => $reportData,
            'dateRange' => ['from' => $startDate, 'to' => $endDate],
        ];
    }
}
