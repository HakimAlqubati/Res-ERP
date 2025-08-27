<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class InventoryManagementCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';
    public static function getNavigationLabel(): string
    {
        return __('menu.inventory_management');
    }
}
