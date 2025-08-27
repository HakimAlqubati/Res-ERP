<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SupplierStoresReportsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';
    public static function getNavigationLabel(): string
    {
        return __('menu.stores_cluster');
    }
}
