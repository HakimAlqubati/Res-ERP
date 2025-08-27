<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRServiceRequestCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Service Request';
    }
}
