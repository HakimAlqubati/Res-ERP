<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRTaskReport extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $clusterBreadcrumb = 'HR';
    public static function getNavigationLabel(): string
    { 
        return __('menu.task_reports');
    }
}
