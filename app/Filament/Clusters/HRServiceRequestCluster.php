<?php

namespace App\Filament\Clusters;

use App\Models\ServiceRequest;
use Filament\Clusters\Cluster;
use Filament\Support\Colors\Color;

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
    public static function getNavigationBadge(): ?string
    {
        return null;
        return ServiceRequest::count();
    }

    /**
     * @return string | array<string> | null
     */
    public static function getNavigationBadgeColor(): string | array | null
    {
        return Color::Red;
    }
}
