<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRCircularCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-speaker-wave';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Engagement';
    }
}
