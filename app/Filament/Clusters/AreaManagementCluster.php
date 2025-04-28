<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AreaManagementCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    public static function getNavigationLabel(): string
    {
        return __('menu.area_management');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_district',
            'view_any_country',
            'view_any_city'
        ])) {
            return true;
        }
        return false;
    }
}
