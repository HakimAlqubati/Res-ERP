<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRServiceRequestCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.hr_service_request_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.service_requests');
    }
}
