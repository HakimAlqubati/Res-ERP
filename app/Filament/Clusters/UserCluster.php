<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class UserCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';
    public static function getNavigationLabel(): string
    {
        return __('lang.user_and_roles');
    }
}
