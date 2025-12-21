<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRApplicationsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.hr_applications_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.hr_applications_cluster');
    }
}
