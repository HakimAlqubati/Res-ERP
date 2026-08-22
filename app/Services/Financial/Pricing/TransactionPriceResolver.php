<?php

namespace App\Services\Financial\Pricing;

use App\Contracts\InventoryPriceResolver;
use App\Models\StockAdjustmentDetail;
use App\Models\StockInventory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolves prices from actual inventory transactions (FIFO-based cost).
 *
 * For finalized inventories:
 *   - Aggregates (price × quantity) from inventory_transactions linked via
 *     StockAdjustmentDetail → to get the weighted-average actual cost.
 *   - Falls back to UnitTablePriceResolver for items without transactions
 *     (e.g. difference = 0, no adjustment was created).
 *
 * For non-finalized inventories:
 *   - Delegates entirely to UnitTablePriceResolver (no transactions exist yet).
 */
class TransactionPriceResolver implements InventoryPriceResolver
{
    public function __construct(
        private readonly UnitTablePriceResolver $fallbackResolver,
    ) {}

    public function resolveForInventory(StockInventory $inventory): Collection
    {
        // ── Not finalized → no transactions exist yet, use fallback ──
        if (! $inventory->finalized) {
            return $this->fallbackResolver->resolveForInventory($inventory);
        }

        $inventory->loadMissing('details');

        // ── Single aggregated query: all transaction costs for this inventory ──
        $transactionPrices = $this->loadTransactionPrices($inventory);

        // ── Fallback prices for items without transactions ──
        $fallbackPrices = $this->fallbackResolver->resolveForInventory($inventory);

        // ── Merge: transactions take priority, fallback fills gaps ──
        $result = collect();

        foreach ($inventory->details as $detail) {
            $key = $detail->product_id . '_' . $detail->unit_id;

            if ($result->has($key)) {
                continue;
            }

            $txn = $transactionPrices->get($key);

            if ($txn && $txn->total_qty > 0) {
                $result->put($key, (object) [
                    'unit_price' => (float) ($txn->total_value / $txn->total_qty),
                    'source'     => 'transaction',
                ]);
            } else {
                $fb = $fallbackPrices->get($key);
                $result->put($key, (object) [
                    'unit_price' => $fb ? (float) $fb->unit_price : 0,
                    'source'     => 'fallback',
                ]);
            }
        }

        return $result;
    }

    /**
     * Load aggregated transaction costs grouped by (product_id, unit_id).
     *
     * Uses a single query joining inventory_transactions → stock_adjustment_details
     * filtered by source_id (inventory id) and source_type (StockInventory).
     */
    private function loadTransactionPrices(StockInventory $inventory): Collection
    {
        return DB::table('inventory_transactions as it')
            ->join('stock_adjustment_details as sad', function ($join) {
                $join->on('it.transactionable_id', '=', 'sad.id')
                     ->where('it.transactionable_type', '=', StockAdjustmentDetail::class);
            })
            ->where('sad.source_id', $inventory->id)
            ->where('sad.source_type', StockInventory::class)
            ->whereNull('it.deleted_at')
            ->whereNull('sad.deleted_at')
            ->select(
                'sad.product_id',
                'sad.unit_id',
                DB::raw('SUM(it.price * it.quantity) as total_value'),
                DB::raw('SUM(it.quantity) as total_qty'),
            )
            ->groupBy('sad.product_id', 'sad.unit_id')
            ->get()
            ->keyBy(fn($row) => $row->product_id . '_' . $row->unit_id);
    }
}
