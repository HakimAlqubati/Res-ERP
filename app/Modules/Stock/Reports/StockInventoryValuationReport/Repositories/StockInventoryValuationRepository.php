<?php

namespace App\Modules\Stock\Reports\StockInventoryValuationReport\Repositories;

use App\Models\StockInventory;
use App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts\StockInventoryValuationRepositoryInterface;
use Illuminate\Support\Collection;

class StockInventoryValuationRepository implements StockInventoryValuationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getInventoriesByStoreAndDate(int $storeId, string $inventoryDate, ?int $categoryId = null): Collection
    {
        return StockInventory::with([
            'store',
            'details' => function ($query) use ($categoryId) {
                if ($categoryId) {
                    $query->whereHas('product', function ($q) use ($categoryId) {
                        $q->where('category_id', $categoryId);
                    });
                }
            },
            'details.product',
            'details.unit',
        ])
        ->where('store_id', $storeId)
        ->where('inventory_date', $inventoryDate)
        ->when($categoryId, function ($query) use ($categoryId) {
            $query->whereHas('details.product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        })
        ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableDatesByStore(int $storeId): array
    {
        return StockInventory::where('store_id', $storeId)
            ->distinct()
            ->orderByDesc('inventory_date')
            ->pluck('inventory_date', 'inventory_date')
            ->toArray();
    }
}
