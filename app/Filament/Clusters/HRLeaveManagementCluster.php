<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRLeaveManagementCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    // protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Attendance Management';
        return __('menu.leave_management');
    }
}
