<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return __('lang.departments_and_employees');
    }
 
}
