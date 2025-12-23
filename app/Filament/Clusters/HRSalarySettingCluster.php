<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRSalarySettingCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    // protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return __('lang.payroll_settings');
    }
}
