<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.hr_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.hr_cluster');
    }
}
