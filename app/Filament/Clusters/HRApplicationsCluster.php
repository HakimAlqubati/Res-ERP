<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRApplicationsCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Requests';
    }
    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_employee-application'
        ])) {
            return true;
        }
        return false;
    }
}
