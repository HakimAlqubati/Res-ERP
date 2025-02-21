<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages\ListInventoryTransactionTruckingReport;

use App\Models\InventoryTransaction;
use App\Models\Product; 
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionTruckingReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
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
    protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 6;


    public static function table(Table $table): Table
    {
        return $table
            ->filters([

                SelectFilter::make("product_id")
                    ->label(__('lang.product'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(Product::active()->get()->pluck('name', 'id')),
            ],FiltersLayout::AboveContent);
    }

  
    public static function getPages(): array
    {
        return [
            'index' => ListInventoryTransactionTruckingReport::route('/'),
        ];
    }
}
