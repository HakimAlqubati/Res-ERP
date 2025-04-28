<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ProductUnitCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    public static function getNavigationLabel(): string
    {
        return __('lang.products_and_units');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission(['view_any_product', 'view_any_category', 'view_any_unit'])) {
            return true;
        }
        return false;
    }
}
