<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HrClusteReport extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        if(isStuff()){
            return 'My Records';
        }
        return 'Reports';
        return __('lang.attednance_reports');
    }
}
