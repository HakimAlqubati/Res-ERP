<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ProductUnitCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    public static function getNavigationLabel(): string
    {
        return __('lang.products_and_units');
    }
}
