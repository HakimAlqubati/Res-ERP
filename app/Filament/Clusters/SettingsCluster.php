<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SettingsCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    public static function getNavigationLabel(): string
    {
        return __('menu.settings');
    }
}
