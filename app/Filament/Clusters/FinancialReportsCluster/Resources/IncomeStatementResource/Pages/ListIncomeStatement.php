<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\IncomeStatementResource\Pages;

use App\Filament\Clusters\FinancialReportsCluster\Resources\IncomeStatementResource;
use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Services\Financial\FinancialReportService;
use App\Services\Financial\MultiBranchFinancialReportService;
use Filament\Resources\Pages\ListRecords;

class ListIncomeStatement extends ListRecords
{
    protected static string $resource = IncomeStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions
        ];
    }

    public function getView(): string
    {
        $filters = $this->getTable()->getFilters();
        $reportType = $filters['report_type']->getState()['type'] ?? 'single';

        if ($reportType === 'comparison') {
            return 'filament.pages.financial-reports.income-statement-multiple-branches';
        }

        return 'filament.pages.financial-reports.income-statement';
    }

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();

        $reportTypeState = $filters['report_type']->getState() ?? [];
        $reportType = $reportTypeState['type'] ?? 'single';
        $branchId = $reportTypeState['branch_id'] ?? null;
        $branchIds = $reportTypeState['branch_ids'] ?? [];

        $dateRange = $filters['date_range']->getState() ?? [];

        // Defaults if not set
        $selectedMonth = $dateRange['month'] ?? now()->format('F Y');
        $date = \Carbon\Carbon::parse($selectedMonth);

        $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $date->copy()->endOfMonth()->format('Y-m-d');

        // Comparison Mode - Multiple Branches
        if ($reportType === 'comparison' && !empty($branchIds)) {
            $service = new MultiBranchFinancialReportService();
            $comparisonData = $service->getComparisonTable(
                array_map('intval', $branchIds),
                $startDate,
                $endDate
            );

            return [
                'reportType' => 'comparison',
                'comparisonData' => $comparisonData,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ];
        }

        // Single Branch Mode (Original Behavior)
        $dto = new IncomeStatementRequestDTO(
            startDate: $startDate,
            endDate: $endDate,
            branchId: $branchId ? (int) $branchId : null
        );

        $service = new FinancialReportService();
        $report = $service->getIncomeStatement($dto);

        $branchName = null;
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            $branchName = $branch ? $branch->name : null;
        }

        return [
            'reportType' => 'single',
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'branchId' => $branchId,
            'branchName' => $branchName,
        ];
    }
}
