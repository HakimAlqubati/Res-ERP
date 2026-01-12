<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class FinanceFormattingCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    
    public static function getNavigationLabel(): string
    {
        return __('menu.finance_formatting');
    }
}
