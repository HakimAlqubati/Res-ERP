<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Resources\PurchaseInvoiceReportResource\Reports\Pages\ListBranchStoreReport;
use App\Models\FakeModelReports\BranchStoreReport;
use Filament\Resources\Resource;

class BranchStoreReportResource extends Resource
{
    protected static ?string $model = BranchStoreReport::class;
    protected static ?string $cluster = InventoryCluster::class;
    protected static ?string $slug = 'branch-store-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
     
    /**
     * @deprecated Use `getModelLabel()` instead.
     */


  
    public static function getLabel(): ?string
    {
        return __('lang.branch_store_report');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.branch_store_report');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranchStoreReport::route('/'),
        ];
    }
}
