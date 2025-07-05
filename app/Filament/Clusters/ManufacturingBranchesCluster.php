<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ManufacturingBranchesCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    public static function getNavigationLabel(): string
    {
        return __('menu.manufacturing_branches');
    }
}