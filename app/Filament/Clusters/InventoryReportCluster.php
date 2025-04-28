<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class InventoryReportCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    public static function getNavigationLabel(): string
    {
        return __('menu.inventory_reports');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission(['view_any_inventory-transaction-trucking-report'])) {
            return true;
        }
        return false;
    }
}
