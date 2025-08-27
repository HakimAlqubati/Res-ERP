<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class OrderReportsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';
    public static function getNavigationLabel(): string
    {
        return __('menu.order_reports');
    }
}
