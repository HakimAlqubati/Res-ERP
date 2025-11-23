<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialComparisonReportResource\Pages;

use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialComparisonReportResource;
use App\Services\Financial\Filters\FinancialCategoryReportFilter;
use App\Services\Financial\Reports\FinancialCategoryReportService;
use Filament\Resources\Pages\ListRecords;

class ListFinancialComparisonReport extends ListRecords
{
    protected static string $resource = FinancialComparisonReportResource::class;

    protected string $view = 'filament.pages.financial-reports.financial-comparison-report';

    protected function getHeaderActions(): array
    {
        return [
            // No actions
        ];
    }

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();

        $periodOne = $filters['period_one']->getState() ?? [];
        $periodTwo = $filters['period_two']->getState() ?? [];
        $branchId = $filters['branch_id']->getState()['value'] ?? null;

        // Defaults if not set
        $periodOneStart = $periodOne['start_date'] ?? now()->subMonth()->startOfMonth()->format('Y-m-d');
        $periodOneEnd = $periodOne['end_date'] ?? now()->subMonth()->endOfMonth()->format('Y-m-d');

        $periodTwoStart = $periodTwo['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $periodTwoEnd = $periodTwo['end_date'] ?? now()->endOfMonth()->format('Y-m-d');

        // Create filters for service
        $filterOneParams = [
            'start_date' => $periodOneStart,
            'end_date' => $periodOneEnd,
            'branch_id' => $branchId,
        ];

        $filterTwoParams = [
            'start_date' => $periodTwoStart,
            'end_date' => $periodTwoEnd,
            'branch_id' => $branchId,
        ];

        $filterOne = new FinancialCategoryReportFilter($filterOneParams);
        $filterTwo = new FinancialCategoryReportFilter($filterTwoParams);

        // Use service to generate comparison
        // Note: The service requires a filter in constructor, we can use either or a dummy one, 
        // but generateComparisonReport takes two specific filters.
        $service = new FinancialCategoryReportService($filterOne);
        $comparisonData = $service->generateComparisonReport($filterOne, $filterTwo);

        return [
            'comparisonData' => $comparisonData,
            'filters' => [
                'period_one' => $filterOneParams,
                'period_two' => $filterTwoParams,
            ],
        ];
    }
}
