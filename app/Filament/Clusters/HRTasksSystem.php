<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\SubNavigationPosition;

class HRTasksSystem extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $clusterBreadcrumb = 'HR';

    public static function getNavigationLabel(): string
    {
        return 'Task Management';
        return __('lang.tasks_ms');
    }
}
