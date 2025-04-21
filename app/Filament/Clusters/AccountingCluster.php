<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AccountingCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $clusterBreadcrumb = 'ACCOUNTING';
    public static function getNavigationLabel(): string
    {
        return __('lang.accounting_system');
    }
 
}
