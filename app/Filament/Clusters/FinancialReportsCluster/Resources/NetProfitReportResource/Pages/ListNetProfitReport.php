<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\NetProfitReportResource\Pages;

use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Filament\Clusters\FinancialReportsCluster\Resources\NetProfitReportResource;
use App\Services\Financial\FinancialReportService;
use Filament\Resources\Pages\ListRecords;

class ListNetProfitReport extends ListRecords
{
    protected static string $resource = NetProfitReportResource::class;

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
            return 'filament.pages.financial-reports.net-profit-multiple-branches';
        }

        return 'filament.pages.financial-reports.net-profit-statement';
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
            // Reusing multi branch if it exists, otherwise we'd need MultiBranchFinancialReportService update
            // For now, we'll try to use the same logic if possible or skip for net profit specifically if not requested
            // We assume MultiBranchFinancialReportService has a net profit comparison or uses the same base.
            // But since user just requested Net Profit Report, we will focus on Single initially.
            // If MultiBranch is required, we use the original for now and see if it needs update.
            $service = new \App\Services\Financial\MultiBranchFinancialReportService();
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

        // Single Branch Mode (Using new getNetProfitReport)
        $dto = new IncomeStatementRequestDTO(
            startDate: $startDate,
            endDate: $endDate,
            branchId: $branchId ? (int) $branchId : null
        );

        $service = new FinancialReportService();
        $report = $service->getNetProfitReport($dto);

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
