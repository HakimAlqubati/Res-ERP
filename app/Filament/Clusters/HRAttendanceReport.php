<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRAttendanceReport extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.hr_reports_cluster');
    }

    public static function getNavigationLabel(): string
    {
        if (isStuff()) {
            return __('lang.my_records');
        }
        return __('lang.hr_reports_cluster');
    }
}
