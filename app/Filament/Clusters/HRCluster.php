<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    public static function getNavigationLabel(): string
    {
        return __('lang.departments_and_employees');
    }

}
