<?php

namespace App\Contracts;

use App\Models\StockInventory;
use App\Services\Financial\Pricing\TransactionPriceResolver;
use App\Services\Financial\Pricing\UnitTablePriceResolver;
use Illuminate\Support\Collection;

/**
 * Contract for resolving unit prices for inventory valuation.
 *
 * Implementations provide different pricing strategies:
 * - UnitTablePriceResolver  → from `unit_prices` table (static pricing)
 * - TransactionPriceResolver → from `inventory_transactions` (actual FIFO cost)
 *
 * Switch strategy by changing the binding in AppServiceProvider::register().
 *
 * @see UnitTablePriceResolver
 * @see TransactionPriceResolver
 */
interface InventoryPriceResolver
{
    /**
     * Resolve unit prices for all details in a stock inventory.
     *
     * Returns a Collection keyed by "{product_id}_{unit_id}" with objects:
     *   - unit_price: float  (price per single unit)
     *   - source: string     ('unit_table' | 'transaction' | 'fallback')
     *
     * @return Collection<string, object{unit_price: float, source: string}>
     */
    public function resolveForInventory(StockInventory $inventory): Collection;
}
