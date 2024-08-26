<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class OrderCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getNavigationLabel(): string
    {
        return 'test';
    }
    public static function getSlug(): string
    {
        return 'order_management_system';
    }
}
