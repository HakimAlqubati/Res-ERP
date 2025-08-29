<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Filament\Support\Enums\IconSize;
class OrderReportsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::ChartPie;
    public static function getNavigationLabel(): string
    {
        return __('menu.order_reports');
    }
}
