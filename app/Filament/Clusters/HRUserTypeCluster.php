<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class HRUserTypeCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon =  Heroicon::OutlinedAdjustmentsHorizontal;

    public static function getClusterBreadcrumb(): ?string
    {
        return __('User Types & Workflow');
    }

    public static function getNavigationLabel(): string
    {
        return __('User Types & Workflow');
    }
}
