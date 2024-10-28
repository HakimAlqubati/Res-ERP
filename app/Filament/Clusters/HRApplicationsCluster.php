<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRApplicationsCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Requests';
    }
}
