<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Pages;

use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource;
use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Widgets\FinicialStatisticsChart;
use Filament\Resources\Pages\Page;


class CustomPage extends Page
{
    protected static string $resource = FinancialStatisticsReportResource::class;
    protected static ?string $slug = 'custom-page';

    public function getColumns(): int|array
    {
        return 3;
    }
     protected string $view = 'filament.clusters.financial-reports-cluster.resources.financial-statistics-report-resource.pages.custom-page';
    public function getWidgets(): array
    {
        return [
            FinicialStatisticsChart::class,
        ];
    }
}
