<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\InventoryReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Models\FakeModelReports\StoreReportReport;
use App\Filament\Resources\PurchaseInvoiceReportResource\Reports\Pages\ListStoresReport;
use App\Models\Branch;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StoresReportResource extends Resource
{
    protected static ?string $model = StoreReportReport::class;
    protected static ?string $slug = 'stores-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
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

    public static function table(Table $table): Table
    {
        return $table->filters([
            SelectFilter::make("branch_id")
                ->label(__('lang.branch'))
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Branch::get()->pluck('name', 'id')),
            Filter::make('date')
                ->form([
                    DatePicker::make('start_date')
                        ->label(__('lang.start_date')),
                    DatePicker::make('end_date')
                        ->label(__('lang.end_date')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query;
                }),
            SelectFilter::make("product_id")
                ->label(__('lang.product'))
                ->multiple()
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Product::where('active', 1)->get()->pluck('name', 'id')),
        ],FiltersLayout::AboveContent);
    }

}
