<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRAttendanceReport extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    public static function getNavigationLabel(): string
    {
        return 'Reports';
        return __('lang.attednance_reports');
    }
}
