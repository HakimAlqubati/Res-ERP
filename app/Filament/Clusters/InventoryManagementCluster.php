<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class InventoryManagementCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    public static function getNavigationLabel(): string
    {
        return __('menu.inventory_management');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_stock-supply-order',
            'view_any_stock-issue-order',
            'view_any_stock-adjustment-reason',
            'view_any_stock-inventory-detail'
        ])) {
            return true;
        }
        return false;
    }
}
