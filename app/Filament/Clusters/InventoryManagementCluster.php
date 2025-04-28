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
            'view_any_stock_supply_order',
            'view_any_stock_issue_order',
            'view_any_stock_adjustment_reason',
            'view_any_stock_inventory_detail'
        ])) {
            return true;
        }
        return false;
    }
}
