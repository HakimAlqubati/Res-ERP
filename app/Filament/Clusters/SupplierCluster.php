<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SupplierCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';
    public static function getNavigationLabel(): string
    {
        return __('lang.supplier_supplier_invoice');
    }
}
