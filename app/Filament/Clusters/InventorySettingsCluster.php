<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class InventorySettingsCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    // protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Supply & Inventory Settings';
    }
    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_stock-adjustment-reason',
            'view_any_payment-method',
        ])) {
            return true;
        }
        return false;
    }
}
