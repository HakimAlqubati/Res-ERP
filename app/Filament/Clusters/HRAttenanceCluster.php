<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRAttenanceCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-finger-print';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.hr_attendance_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.attendance_management');
    }
}
