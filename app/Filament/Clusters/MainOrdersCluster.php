<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class MainOrdersCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';
    public static function getNavigationLabel(): string
    {
        return __('menu.branch_orders');
    }
}
