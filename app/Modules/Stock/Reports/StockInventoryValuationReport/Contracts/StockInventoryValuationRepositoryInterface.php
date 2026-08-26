<?php

namespace App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts;

use Illuminate\Support\Collection;

interface StockInventoryValuationRepositoryInterface
{
    /**
     * Fetch all stock inventories for a specific store and date.
     *
     * @param int $storeId
     * @param string $inventoryDate
     * @return Collection
     */
    public function getInventoriesByStoreAndDate(int $storeId, string $inventoryDate): Collection;

    /**
     * Fetch unique/distinct inventory dates for a specific store.
     *
     * @param int $storeId
     * @return array<string, string>
     */
    public function getAvailableDatesByStore(int $storeId): array;
}
