<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Pages;

use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource;
use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Widgets\FinicialStatisticsChart;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Models\Branch;
use Illuminate\Contracts\Support\Htmlable;

class CustomPage extends Page
{

    protected static string $resource = FinancialStatisticsReportResource::class;
    protected static ?string $slug = 'custom-page';
    protected static ?string $title = null;
    public function getHeading(): string | Htmlable
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getTitle(): string | Htmlable
    {
        return '';
    }


    public function getModelLabel(): ?string
    {
        return null;
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected string $view = 'filament.clusters.financial-reports-cluster.resources.financial-statistics-report-resource.pages.custom-page';

    public function getWidgets(): array
    {
        return [
            FinicialStatisticsChart::class,
        ];
    }
}
