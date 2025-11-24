<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources;

use App\Filament\Clusters\FinancialReportsCluster;
use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Pages\CustomPage;
use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Pages\ListFinancialStatisticsReport;
use App\Models\FinancialCategory; // Using FinancialCategory as a dummy model base, or could use a different one if more appropriate
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class FinancialStatisticsReportResource extends Resource
{
    protected static ?string $model = FinancialCategory::class; // We might not need a specific model if it's a pure report, but Resource requires one.

    protected static ?string $slug = 'financial-statistics-report';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $cluster = FinancialReportsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;

    public static function getLabel(): ?string
    {
        return __('Financial Statistics Report');
    }

    public static function getNavigationLabel(): string
    {
        return __('Statistics Report');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Financial Statistics Reports');
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialStatisticsReport::route('/'),
            // 'index' => CustomPage::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isFinanceManager();
    }
    public static function getNavigationBadge(): ?string
    {
        return __('Report');
    }
}
