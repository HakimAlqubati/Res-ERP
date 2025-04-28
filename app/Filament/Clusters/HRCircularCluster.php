<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRCircularCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Engagement';
    }
    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_circular',
        ])) {
            return true;
        }
        return false;
    }
}
