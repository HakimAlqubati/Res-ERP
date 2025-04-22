<?php
// path: app/Filament/Clusters/AccountingCluster/AccountingReportsCluster/Resources/ProfitLossReportResource/Pages/ListProfitLossReport.php

namespace App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\ProfitLossReportResource\Pages;

use App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\ProfitLossReportResource;
use App\Models\Account;
use Filament\Resources\Pages\ListRecords;

class ListProfitLossReport extends ListRecords
{
    protected static string $resource = ProfitLossReportResource::class;

    protected static string $view = 'filament.pages.accounting-reports.profit-loss-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters()['date_range']->getState() ?? [];

        $startDate = $filters['start_date'] ?? now()->startOfYear();
        $endDate = $filters['end_date'] ?? now();

        $revenueAccounts = Account::where('type', Account::TYPE_REVENUE)
            ->with(['lines' => fn($q) => $q->whereHas(
                'journalEntry',
                fn($q2) =>
                $q2->whereBetween('date', [$startDate, $endDate])
            )])
            ->get();

        $expenseAccounts = Account::where('type', Account::TYPE_EXPENSE)
            ->with(['lines' => fn($q) => $q->whereHas(
                'journalEntry',
                fn($q2) =>
                $q2->whereBetween('date', [$startDate, $endDate])
            )])
            ->get();

        $revenues = $revenueAccounts->map(fn($account) => [
            'name' => $account->name,
            'code' => $account->code,
            'amount' => $account->lines->sum('credit'),
        ]);

        $expenses = $expenseAccounts->map(fn($account) => [
            'name' => $account->name,
            'code' => $account->code,
            'amount' => $account->lines->sum('debit'),
        ]);

        $totalRevenue = $revenues->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $netProfit = $totalRevenue - $totalExpenses;

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'totals' => [
                'total_revenue' => $totalRevenue,
                'total_expense' => $totalExpenses,
                'net_profit' => $netProfit,
            ],
            'dateRange' => ['from' => $startDate, 'to' => $endDate],
        ];
    }
}
