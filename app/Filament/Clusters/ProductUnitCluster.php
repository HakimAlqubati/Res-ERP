<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ProductUnitCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Cube;
    public static function getNavigationLabel(): string
    {
        return __('lang.products_and_units');
    }
}
