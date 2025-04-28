<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRSalarySettingCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    // protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Payroll Settings';
    }
    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_allowance',
            'view_any_deduction',
            'view_any_monthly-incentive',
        ])) {
            return true;
        }
        return false;
    }
}
