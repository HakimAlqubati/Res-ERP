<?php

namespace App\Filament\Clusters\AccountingReports;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class AccountingReportsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.accounting_reports_cluster');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.accounting_reports_cluster');
    }
}
