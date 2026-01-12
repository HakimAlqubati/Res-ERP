<?php

namespace App\Filament\Clusters\AccountingReports\Resources\AccountingIncomeStatementResource\Pages;

use App\Filament\Clusters\AccountingReports\Resources\AccountingIncomeStatementResource;
use App\DTOs\Accounting\AccountingIncomeStatementRequestDTO;
use App\Models\Branch;
use App\Services\Accounting\AccountingIncomeStatementService;
use Filament\Resources\Pages\ListRecords;

class ListAccountingIncomeStatement extends ListRecords
{
    protected static string $resource = AccountingIncomeStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getView(): string
    {
        return 'filament.pages.accounting-reports.accounting-income-statement';
    }

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();

        $dateRange = $filters['date_range']->getState() ?? [];
        $branchId = $filters['branch_id']->getState()['value'] ?? null;

        $startDate = $dateRange['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $dateRange['end_date'] ?? now()->endOfMonth()->format('Y-m-d');

        $dto = new AccountingIncomeStatementRequestDTO(
            startDate: $startDate,
            endDate: $endDate,
            branchId: $branchId ? (int) $branchId : null
        );

        $service = new AccountingIncomeStatementService();
        $report = $service->getIncomeStatement($dto);

        $branchName = $branchId ? Branch::find($branchId)?->name : null;

        return [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'branchName' => $branchName,
        ];
    }
}
