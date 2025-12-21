<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\SubNavigationPosition;

class HRTasksSystem extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.hr_tasks_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.task_management');
    }
}
