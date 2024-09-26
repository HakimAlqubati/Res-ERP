<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRServiceRequestCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    public static function getNavigationLabel(): string
    {
        return 'Service Request';
    }
}
