<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return __('lang.departments_and_employees');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission(['view_any_employee','view_any_position','view_any_employee-file-type'])) {
            return true;
        }
        return false;
    }
}
