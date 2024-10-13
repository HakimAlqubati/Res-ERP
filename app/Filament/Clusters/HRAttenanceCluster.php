<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRAttenanceCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Attendance Management';
        return __('lang.attenance_management');
    }
}
