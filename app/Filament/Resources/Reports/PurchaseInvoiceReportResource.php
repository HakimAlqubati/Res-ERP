<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\InventoryReportsCluster;
use App\Models\FakeModelReports\PurchaseInvoiceReport;
use App\Filament\Resources\PurchaseInvoiceReportResource\Reports\Pages\ListPurchaseInvoiceReport;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource; 

class PurchaseInvoiceReportResource extends Resource
{
    protected static ?string $model = PurchaseInvoiceReport::class;
    protected static ?string $slug = 'purchase-invoice-reports';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = InventoryReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.purchase_invoice_report');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.purchase_invoice_report');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.purchase_invoice_report');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseInvoiceReport::route('/'),
        ];
    }
}
