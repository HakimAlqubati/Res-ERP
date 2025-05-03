<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\InventoryReportCluster;
use App\Models\Product;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MissingInventoryProductsReportResource\Pages\ListMissingInventoryProductsReport;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class MissingInventoryProductsReportResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 11;

    public static function getPluralLabel(): ?string
    {
        return 'Missing Inventory Products Report';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Missing Inventory Products Report';
    }


    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')->live()
                            // ->afterStateUpdated(function (Set $set, $state) {
                            //     $endNextMonthData = getEndOfMonthDate(Carbon::parse($state)->year, Carbon::parse($state)->month);
                            //     $set('end_date', $endNextMonthData['end_month']);
                            // })
                            ->label('Start Date')
                            ->default(now()->firstOfMonth()), // Use function for dynamic default value

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(now()->endOfMonth()),
                    ]),
                SelectFilter::make('store_id')->label('Store')->options(
                    function () {
                        return Store::active()
                            ->get(['name', 'id'])->pluck('name', 'id')->toArray();
                    }
                )

                    ->hidden(fn() => isStuff() || isMaintenanceManager())
                    ->searchable(),
            ], FiltersLayout::AboveContent)
            ->actions([]);
    }



    public static function getPages(): array
    {
        return [
            'index' => ListMissingInventoryProductsReport::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 0;
        return static::getModel()::whereNotNull('minimum_stock_qty')->count();
    }
}
