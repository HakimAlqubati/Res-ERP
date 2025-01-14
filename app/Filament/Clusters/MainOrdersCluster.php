<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class MainOrdersCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    public static function getNavigationLabel(): string
    {
        return __('lang.orders');
    }
}
