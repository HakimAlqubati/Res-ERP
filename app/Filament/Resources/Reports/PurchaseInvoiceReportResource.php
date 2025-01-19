<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Clusters\InventoryReportsCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Resources\PurchaseInvoiceReportResource\Reports\Pages\ListPurchaseInvoiceReport;
use App\Models\FakeModelReports\PurchaseInvoiceReport;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Store;
use App\Models\Supplier;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseInvoiceReportResource extends Resource
{
    protected static ?string $model = PurchaseInvoiceReport::class;
    protected static ?string $slug = 'purchase-invoice-reports';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;

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

    public static function table(Table $table): Table
    {
        return $table->filters([
            SelectFilter::make("store_id")
                ->searchable()
                ->label(__('lang.store'))
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Store::get()->pluck('name', 'id')),

            SelectFilter::make("supplier_id")
                ->searchable()
                ->label(__('lang.supplier'))
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Supplier::get()->pluck('name', 'id')),
            SelectFilter::make("product_id")
                ->searchable()
                ->label(__('lang.product'))
                ->multiple()
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Product::where('active', 1)->get()->pluck('name', 'id')),

            SelectFilter::make("invoice_no")
                ->searchable()
                ->label(__('lang.invoice_no'))
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(PurchaseInvoice::get()->pluck('invoice_no', 'invoice_no')),
            Filter::make('show_invoice_no')->label(__('lang.show_invoice_no'))
            ,
        ], FiltersLayout::AboveContent);
    }

}
