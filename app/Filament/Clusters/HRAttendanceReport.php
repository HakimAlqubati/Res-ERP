<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRAttendanceReport extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        if(isStuff()){
            return 'My Records';
        }
        return 'Reports';
        return __('lang.attednance_reports');
    }
    public static function canAccess(): bool
    {
        return false;
    }
}
