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

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_work-period',
            'view_any_attendance',
            'view_any_employee-overtime',
            'view_any_attendance-images-uploaded'
        ])) {
            return true;
        }
        return false;
    }
}
