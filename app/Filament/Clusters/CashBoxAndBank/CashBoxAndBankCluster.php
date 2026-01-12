<?php

namespace App\Filament\Clusters\CashBoxAndBank;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class CashBoxAndBankCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.cash_box_and_bank_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.cash_box_and_bank_cluster');
    }
}
