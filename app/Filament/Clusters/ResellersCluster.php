<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ResellersCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';
    public static function getNavigationLabel(): string
    {
        return __('menu.resellers');
    }
}
