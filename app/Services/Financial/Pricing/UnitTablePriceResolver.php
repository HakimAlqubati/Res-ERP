<?php

namespace App\Services\Financial\Pricing;

use App\Contracts\InventoryPriceResolver;
use App\Models\StockInventory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolves prices from the `unit_prices` table (static/standard pricing).
 *
 * This is the ORIGINAL pricing strategy — uses a single batch query
 * to load all relevant prices at once (no N+1).
 */
class UnitTablePriceResolver implements InventoryPriceResolver
{
    public function resolveForInventory(StockInventory $inventory): Collection
    {
        $inventory->loadMissing('details');

        $productIds = $inventory->details->pluck('product_id')->unique()->values();
        $unitIds    = $inventory->details->pluck('unit_id')->unique()->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        // ── Single batch query instead of N calls to getUnitPrice() ──
        $prices = DB::table('unit_prices')
            ->whereNull('deleted_at')
            ->whereIn('product_id', $productIds)
            ->whereIn('unit_id', $unitIds)
            ->get(['product_id', 'unit_id', 'price'])
            ->keyBy(fn($row) => $row->product_id . '_' . $row->unit_id);

        // Build result keyed by "productId_unitId"
        $result = collect();

        foreach ($inventory->details as $detail) {
            $key  = $detail->product_id . '_' . $detail->unit_id;

            if ($result->has($key)) {
                continue; // already resolved (duplicate product+unit pair)
            }

            $row = $prices->get($key);

            $result->put($key, (object) [
                'unit_price' => (float) ($row->price ?? 0),
                'source'     => 'unit_table',
            ]);
        }

        return $result;
    }
}
