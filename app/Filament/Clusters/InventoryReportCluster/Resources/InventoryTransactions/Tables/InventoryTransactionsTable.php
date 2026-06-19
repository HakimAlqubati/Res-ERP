<?php

namespace App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Tables;

use App\Models\Product;
use App\Models\Store;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->filters([
                SelectFilter::make('store_id')
                    ->label(__('lang.store'))
                    ->searchable()
                    ->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    )
                    ->placeholder('Select Store')
                    ->query(fn (Builder $q) => $q),

                SelectFilter::make('product_ids')
                    ->label(__('lang.product'))
                    ->multiple()
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::where('active', 1)
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
                    ->query(fn (Builder $q) => $q),

                SelectFilter::make('current_batch')
                    ->label('Batch Type')
                    ->options([
                        '1' => 'Current Batch Only',
                        '0' => 'Non-Current Batches',
                    ])
                    ->placeholder('All Batches')
                    ->query(fn (Builder $q) => $q),
            ], FiltersLayout::AboveContent)
            ->deferFilters(false);
    }
}
