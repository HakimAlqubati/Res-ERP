<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class OrderReportsCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    public static function getNavigationLabel(): string
    {
        return __('menu.order_reports');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
