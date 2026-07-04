<?php

namespace App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Tables;

use App\Models\Product;
use App\Models\Store;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompoundProductComponentsStockReportTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->filters([
                SelectFilter::make('store_id')
                    ->label(__('lang.store'))
                    ->searchable()
                    ->options(
                        Store::active()
                            ->whereHas('branches', function ($query) {
                                $query->where('type', \App\Models\Branch::TYPE_CENTRAL_KITCHEN);
                            })
                            ->get()
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->placeholder('Select Store')
                    ->query(fn (Builder $q) => $q),

                SelectFilter::make('compound_product_id')
                    ->label('Compound Product')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::where('active', 1)
                            ->manufacturingCategory()
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($product) => [
                                $product->id => "{$product->code} - {$product->name}",
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelsUsing(function (array $values): array {
                        return Product::whereIn('id', $values)
                            ->get()
                            ->mapWithKeys(fn ($product) => [
                                $product->id => "{$product->code} - {$product->name}",
                            ])
                            ->toArray();
                    })
                    ->options(function () {
                        return Product::where('active', 1)
                            ->manufacturingCategory()
                            ->limit(5)
                            ->get()
                            ->mapWithKeys(fn ($product) => [
                                $product->id => "{$product->code} - {$product->name}",
                            ])
                            ->toArray();
                    })
                    ->query(fn (Builder $q) => $q),
            ], FiltersLayout::AboveContent)
            ->deferFilters(false);
    }
}
