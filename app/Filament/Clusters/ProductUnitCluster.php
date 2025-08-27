<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ProductUnitCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';
    public static function getNavigationLabel(): string
    {
        return __('lang.products_and_units');
    }
}
