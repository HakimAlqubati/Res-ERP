<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Illuminate\Database\Eloquent\Builder;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages\ListMinimumProductQtyReports;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\InventoryReportCluster;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages;
use App\Models\Category;
use App\Models\Store;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;

class MinimumProductQtyReportResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = false;
    public static function getPluralLabel(): ?string
    {
        return 'Minimum Product Qty';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Minimum Product Qty';
    }


    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([])
            ->filters([
                //
            ])->deferFilters(false)
            ->filters([
                SelectFilter::make("store_id")->placeholder('Select Store')
                    ->label(__('lang.store'))->searchable()
                    ->default(fn() => request()->get('store_id'))
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
                SelectFilter::make("store_id")->placeholder('Select Store')
                    ->label(__('lang.store'))->searchable()
                    ->default(fn() => request()->get('store_id'))
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
                SelectFilter::make("category_id")
                    ->label(__('lang.category'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(Category::active()->get()->pluck('name', 'id')),
            ], FiltersLayout::AboveContent)
            ->recordActions([]);
    }



    public static function getPages(): array
    {
        return [
            'index' => ListMinimumProductQtyReports::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
        return static::getModel()::whereNotNull('minimum_stock_qty')->count();
    }
}
