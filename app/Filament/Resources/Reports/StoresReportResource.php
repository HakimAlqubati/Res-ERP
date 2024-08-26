<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Clusters\InventoryCluster;
use App\Models\FakeModelReports\StoreReportReport;
use App\Filament\Resources\PurchaseInvoiceReportResource\Reports\Pages\ListStoresReport;
use Filament\Resources\Resource;

class StoresReportResource extends Resource
{
    protected static ?string $model = StoreReportReport::class;
    protected static ?string $slug = 'stores-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = InventoryCluster::class;
    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.stores_report');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.stores_report');
    }
    public static function getPluralLabel(): ?string
    {
        return __('lang.stores_report');
    }
    public static function getPages(): array
    {
        return [
            'index' => ListStoresReport::route('/'),
        ];
    }
}
