<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class InventorySettingsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog';
    // protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return __('lang.supply_inventory_settings');
    }
}
