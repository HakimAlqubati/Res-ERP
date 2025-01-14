<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ReportOrdersCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    public static function getNavigationLabel(): string
    {
        return __('lang.order_reports');
    }
    
}
