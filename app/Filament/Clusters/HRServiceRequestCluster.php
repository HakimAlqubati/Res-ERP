<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRServiceRequestCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    public static function getNavigationLabel(): string
    {
        return 'Service request';
    }
}
