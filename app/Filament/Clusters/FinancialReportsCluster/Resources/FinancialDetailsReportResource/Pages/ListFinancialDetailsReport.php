<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialDetailsReportResource\Pages;

use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialDetailsReportResource;
use App\Models\FinancialTransaction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFinancialDetailsReport extends ListRecords
{
    protected static string $resource = FinancialDetailsReportResource::class;

    protected string $view = 'filament.pages.financial-reports.financial-details-report';

    protected function getHeaderActions(): array
    {
        return [
            // No create action
        ];
    }

    protected function getViewData(): array
    {
        // Get filter values from the table
        $filters = $this->getTable()->getFilters();

        $transactionDate = $filters['transaction_date']->getState() ?? [];
        $branchId = $filters['branch_id']->getState()['value'] ?? null;
        $categoryId = $filters['category_id']->getState()['value'] ?? null;
        $type = $filters['type']->getState()['value'] ?? null;
        // $status = $filters['status']->getState()['value'] ?? null;

        $startDate = $transactionDate['from'] ?? null;
        $endDate = $transactionDate['until'] ?? null;

        // Build query
        $query = FinancialTransaction::query()
            ->with(['branch', 'category'])
            ->latest('transaction_date');

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        // if ($status) {
        //     $query->where('status', $status);
        // }

        $transactions = $query->get();

        // Calculate totals
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        return [
            'transactions' => $transactions,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'branch_id' => $branchId,
                'category_id' => $categoryId,
                'type' => $type,
                // 'status' => $status,
            ],
            'totals' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'net_balance' => $netBalance,
            ],
        ];
    }
}
