<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialCategoryReportResource\Pages;

use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialCategoryReportResource;
use App\Filament\Traits\HasBackButtonAction;
use App\Services\Financial\Filters\FinancialCategoryReportFilter;
use App\Services\Financial\Reports\FinancialCategoryReportService;
use Filament\Resources\Pages\ListRecords;

class ListFinancialCategoryReport extends ListRecords
{
    use HasBackButtonAction;
    
    protected static string $resource = FinancialCategoryReportResource::class;
    
    protected string $view = 'filament.pages.financial-reports.financial-category-report';

    public $perPage = 15;

    protected function getViewData(): array
    {
        // Get filter values from the table
        $type = $this->getTable()->getFilters()['type']->getState()['value'] ?? null;
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value'] ?? null;
        $dateRange = $this->getTable()->getFilters()['date_range']->getState() ?? [];
        $status = $this->getTable()->getFilters()['status']->getState()['value'] ?? null;
        $options = $this->getTable()->getFilters()['options']->getState() ?? [];

        $startDate = $dateRange['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $dateRange['end_date'] ?? now()->endOfMonth()->format('Y-m-d');
        $showSystemCategories = $options['show_system_categories'] ?? true;
        $showHiddenCategories = $options['show_hidden_categories'] ?? false;

        // Build filter parameters
        $filterParams = array_filter([
            'type' => $type,
            'branch_id' => $branchId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'is_visible' => !$showHiddenCategories ? true : null,
        ]);

        // Create filter and service
        $filter = new FinancialCategoryReportFilter($filterParams);
        $service = new FinancialCategoryReportService($filter);

        // Generate report
        $report = $service->generateReport();
        $reportData = $report->toArray();

        return [
            'reportData' => $reportData,
            'filters' => [
                'type' => $type,
                'branch_id' => $branchId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'show_system_categories' => $showSystemCategories,
                'show_hidden_categories' => $showHiddenCategories,
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // يمكن إضافة actions مثل Export PDF, Excel, etc.
        ];
    }
}
