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

class InventoryTransactionPurchaseReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;
    protected static ?string $slug = 'inventory-p-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getLabel(): ?string
    {
        return 'Inventory by Purchase';
    }
    public static function getNavigationLabel(): string
    {
        return 'Inventory by Purchase';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Inventory by Purchase';
    }
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = -1;


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
                    })->getSearchResultsUsing(function (string $search): array {
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
                SelectFilter::make("store_id")
                    ->label(__('lang.store'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
                // Filter::make('show_extra_fields')
                //     ->label('Show Extra')
                //     ->form([
                //         Toggle::make('only_available')
                //             ->inline(false)
                //             ->label('Show Available in Stock')
                //     ]),
            ], FiltersLayout::AboveContent);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryTransactionPurchaseReport::route('/'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}
