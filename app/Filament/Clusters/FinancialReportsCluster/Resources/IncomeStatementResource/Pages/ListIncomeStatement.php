<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\IncomeStatementResource\Pages;

use App\Filament\Clusters\FinancialReportsCluster\Resources\IncomeStatementResource;
use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Services\Financial\FinancialReportService;
use Filament\Resources\Pages\ListRecords;

class ListIncomeStatement extends ListRecords
{
    protected static string $resource = IncomeStatementResource::class;

    protected string $view = 'filament.pages.financial-reports.income-statement';

    protected function getHeaderActions(): array
    {
        return [
            // No actions
        ];
    }

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();

        $dateRange = $filters['date_range']->getState() ?? [];
        $branchId = $filters['branch_id']->getState()['value'] ?? null;

        // Defaults if not set
        $selectedMonth = $dateRange['month'] ?? now()->format('F Y');
        $date = \Carbon\Carbon::parse($selectedMonth);

        $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $date->copy()->endOfMonth()->format('Y-m-d');

        // Create DTO
        $dto = new IncomeStatementRequestDTO(
            startDate: $startDate,
            endDate: $endDate,
            branchId: $branchId
        );

        // Use service to generate report
        $service = new FinancialReportService();
        $report = $service->getIncomeStatement($dto);

        // dd($report);
        $branchName = null;
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            $branchName = $branch ? $branch->name : null;
        }

        return [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'branchId' => $branchId,
            'branchName' => $branchName,
        ];
    }
}
