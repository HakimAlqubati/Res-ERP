<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRCircularCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';
    public static function getNavigationLabel(): string
    {
        return 'Circulars';
    }
}
