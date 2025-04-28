<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRSalaryCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    {
        return 'Payroll';
    }
    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission([
            'view_any_month-salary',
            'view_any_penalty-deduction',
        ])) {
            return true;
        }
        return false;
    }
}
