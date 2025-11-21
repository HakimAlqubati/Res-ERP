<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class FinancialReportsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';


    // protected static ?string $clusterBreadcrumb = 'acc';

    public static function getNavigationLabel(): string
    {
        return __('menu.financial_reports');
    }
}
