<?php

namespace App\Filament\Clusters\AccountingReports\Resources\TrialBalanceResource\Pages;

use App\Filament\Clusters\AccountingReports\Resources\TrialBalanceResource;
use App\DTOs\Accounting\TrialBalanceRequestDTO;
use App\Services\Accounting\TrialBalanceService;
use Filament\Resources\Pages\ListRecords;

class ListTrialBalance extends ListRecords
{
    protected static string $resource = TrialBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions
        ];
    }

    public function getView(): string
    {
        return 'filament.pages.accounting-reports.trial-balance';
    }

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();

        $dateRange = $filters['date_range']->getState() ?? [];
        $accountType = $filters['account_type']->getState()['value'] ?? null;
        $showZeroBalances = $filters['show_zero_balances']->getState() ?? false;

        // Defaults if not set
        $startDate = $dateRange['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $dateRange['end_date'] ?? now()->endOfMonth()->format('Y-m-d');

        // Convert boolean filter
        $showZeroBalancesBoolean = $showZeroBalances === true || $showZeroBalances === 'true';

        $dto = new TrialBalanceRequestDTO(
            startDate: $startDate,
            endDate: $endDate,
            accountType: $accountType,
            showZeroBalances: $showZeroBalancesBoolean
        );

        $service = new TrialBalanceService();
        $report = $service->getTrialBalance($dto);

        return [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'accountType' => $accountType,
        ];
    }
}
