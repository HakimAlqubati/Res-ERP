<?php

namespace App\Filament\Clusters;

use Filament\Panel;
use Filament\Clusters\Cluster;

class OrderCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getNavigationLabel(): string
    {
        return 'test';
    }
    public static function getSlug(?Panel $panel = null): string
    {
        return 'order_management_system';
    }
}
