<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRSalaryCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    public static function getNavigationLabel(): string
    {
        return 'Salaries';
    }
}
