<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;
    protected static ?string $slug = 'inventory-report';
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
    protected static ?string $cluster = InventoryReportCluster::class;
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
                    })->options(
                        Product::active()->get()->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'label' => "{$product->name} - {$product->id}",
                            ];
                        })->pluck('label', 'id')
                    ),
                SelectFilter::make("store_id")
                    ->label(__('lang.store'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
                Filter::make('show_extra_fields')
                    ->label('Show Extra')
                    ->form([
                        Toggle::make('only_available')
                            ->inline(false)
                            ->label('Show Available in Stock')
                    ]),
            ], FiltersLayout::AboveContent);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryTransactionReport::route('/'),
        ];
    }
}
