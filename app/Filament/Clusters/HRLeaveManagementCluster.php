<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRLeaveManagementCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-finger-print';
    // protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return __('menu.leave_management');
    }
}
