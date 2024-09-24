<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\SubNavigationPosition;

class HRTasksSystem extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    


    public static function getNavigationLabel(): string
    {
        return 'Task Management';
        return __('lang.tasks_ms');
    }
}
