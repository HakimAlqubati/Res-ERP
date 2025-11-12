<?php

namespace App\Filament\Clusters\POSIntegration;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class POSIntegrationCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static ?string $clusterBreadcrumb = 'POS';
    public static function getNavigationLabel(): string
    {
        return __('lang.pos_integration');
    }
}
