<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Store;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getLabel(): ?string
    {
        return 'Inventory Report';
    }
    public static function getNavigationLabel(): string
    {
        return 'Inventory Report';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Inventory Report';
    }
    protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;


    public static function table(Table $table): Table
    {
        return $table
            ->filters([

                SelectFilter::make("category_id")
                    ->label(__('lang.category'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(Category::active()->get()->pluck('name', 'id')),
                SelectFilter::make("product_id")
                    ->label(__('lang.product'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(Product::active()->get()->pluck('name', 'id')),
                SelectFilter::make("store_id")
                    ->label(__('lang.store'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
            ], FiltersLayout::AboveContent);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryTransactionReport::route('/'),
        ];
    }


}
