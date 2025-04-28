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
    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_weekly-holiday',
            'view_any_leave-type',
            'view_any_holiday',
        ])) {
            return true;
        }
        return false;
    }
}
