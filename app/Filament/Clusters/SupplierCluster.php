<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SupplierCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    public static function getNavigationLabel(): string
    {
        return __('lang.supplier_supplier_invoice');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyPermission(['view_any_supplier', 'view_any_purchase_invoice'])) {
            return true;
        }
        return false;
    }
}
