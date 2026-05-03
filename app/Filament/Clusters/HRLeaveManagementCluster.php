<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class HRLeaveManagementCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::CalendarDays;
    // protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return __('menu.leave_management');
    }
}
