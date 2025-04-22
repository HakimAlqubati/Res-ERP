<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AccountingReportCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $clusterBreadcrumb = 'ACCOUNTING_REPORTS';
    public static function getNavigationLabel(): string
    {
        return __('accounting.accounting_reports');
    }
 
}
