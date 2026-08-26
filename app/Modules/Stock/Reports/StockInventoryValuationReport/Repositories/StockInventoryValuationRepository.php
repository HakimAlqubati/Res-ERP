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
    public function getInventoriesByStoreAndDate(int $storeId, string $inventoryDate): Collection
    {
        return StockInventory::with([
            'store',
            'details.product',
            'details.unit',
        ])
        ->where('store_id', $storeId)
        ->where('inventory_date', $inventoryDate)
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
