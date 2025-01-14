<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SupplierCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    public static function getNavigationLabel(): string
    {
        return __('lang.supplier_supplier_invoice');
    }
}
