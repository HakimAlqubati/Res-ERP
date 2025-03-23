<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRTaskReport extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    { 
        return __('menu.task_reports');
    }
}
