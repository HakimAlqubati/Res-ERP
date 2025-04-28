<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SupplierStoresReportsCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    public static function getNavigationLabel(): string
    {
        return __('menu.stores_cluster');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission(['view_any_store', 'view_any_inventory_transaction'])) {
            return true;
        }
        return false;
    }
}
