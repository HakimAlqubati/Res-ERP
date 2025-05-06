<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class InventorySettingsCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    // protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Inventory Settings';
    }
}
