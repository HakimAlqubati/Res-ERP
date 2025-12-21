<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRSalaryCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.hr_salary_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.payroll');
    }
}
