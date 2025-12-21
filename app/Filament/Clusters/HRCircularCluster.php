<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRCircularCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-speaker-wave';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.hr_circular_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.engagement');
    }
}
