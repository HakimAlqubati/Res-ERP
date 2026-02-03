<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages\ListInventoryTransactionTruckingReport;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages\CategoryInventoryTrackingReport;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages\InventorySummaryReport;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class InventoryTransactionTruckingReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;
    public static function getLabel(): ?string
    {
        return 'Inventory Tracking';
    }
    public static function getNavigationLabel(): string
    {
        return 'Inventory Tracking';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Inventory Tracking';
    }
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;


    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->filters([

                SelectFilter::make("product_id")
                    ->label(__('lang.product'))->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::where('active', 1)
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                    ->options(function () {
                        return Product::where('active', 1)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ]);
                    }),
                SelectFilter::make('movement_type')->label('Type')->options([
                    InventoryTransaction::MOVEMENT_IN => 'In',
                    InventoryTransaction::MOVEMENT_OUT => 'Out',
                ]),
                SelectFilter::make('unit_id')->label('Unit')->options(Unit::active()->get(['name', 'id'])->pluck('name', 'id')),
                SelectFilter::make("store_id")->placeholder('Select Store')
                    ->label(__('lang.store'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
                SelectFilter::make('transactionable_type')
                    ->label('Transaction Type')
                    ->options([
                        'App\Models\PurchaseInvoice'   => 'Purchase Invoice',
                        'App\Models\GoodsReceivedNote' => 'Goods Received Note',
                        'App\Models\StockSupplyOrder' => 'Stock Supply',
                        // أضف أي أنواع عمليات أخرى هنا بنفس الطريقة
                    ]),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('From Date'),
                        DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query;
                    }),
            ], FiltersLayout::AboveContent);
    }


    public static function getPages(): array
    {
        return [
            'index' => ListInventoryTransactionTruckingReport::route('/'),
            'tracking_cat' => CategoryInventoryTrackingReport::route('/tracking_cat'),
            'summary_report' => InventorySummaryReport::route('/summary_report'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}
