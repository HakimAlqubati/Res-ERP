<?php

namespace App\Services\Financial;

use App\Enums\FinancialCategoryCode;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\InventoryTransaction;
use App\Models\StockInventory;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClosingStockCalculationService
{
    /**
     * Calculate the total value of closing stock for a StockInventory based on FIFO.
     *
     * @param StockInventory $inventory
     * @return float
     */
    public function calculateClosingStockValue(StockInventory $inventory): float
    {
        $totalValue = 0.0;

        // Load details with product
        $inventory->loadMissing('details.product');

        foreach ($inventory->details as $detail) {
            $physicalQty = (float) $detail->physical_quantity;

            if ($physicalQty <= 0) {
                continue;
            }

            $productId = $detail->product_id;
            $storeId = $inventory->store_id;

            // Calculate value for this product using FIFO
            $productValue = $this->calculateProductValueFifo($productId, $storeId, $physicalQty);
            $totalValue += $productValue;
        }

        return $totalValue;
    }

    /**
     * Calculate value for a single product using FIFO logic.
     *
     * @param int $productId
     * @param int $storeId
     * @param float $quantity
     * @return float
     */
    private function calculateProductValueFifo(int $productId, int $storeId, float $quantity): float
    {
        // Get inbound transactions (purchases, transfers in, adjustments in)
        // Ordered by date DESC (newest first) because we assume remaining stock is from the latest batches
        $inboundTransactions = InventoryTransaction::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $remainingQtyToValue = $quantity;
        $value = 0.0;

        foreach ($inboundTransactions as $transaction) {
            if ($remainingQtyToValue <= 0) {
                break;
            }

            $trxQty = (float) $transaction->quantity;
            $trxPrice = (float) $transaction->price; // Price per unit

            // If price is 0 or null, try to get from product cost or last purchase price
            if ($trxPrice <= 0) {
                // Fallback: This might need adjustment based on business rules
                // For now, we'll skip or use 0, but ideally we should look up product cost
                // Let's try to get it from the product if available, or just use 0
                // $trxPrice = $transaction->product->cost_price ?? 0;
            }

            $qtyToTake = min($remainingQtyToValue, $trxQty);

            $value += $qtyToTake * $trxPrice;
            $remainingQtyToValue -= $qtyToTake;
        }

        // If there is still quantity remaining (more stock than recorded history), 
        // value it at the average cost of the last transaction or product cost
        if ($remainingQtyToValue > 0) {
            // Fallback price: use the price of the last found transaction, or 0
            $lastPrice = 0;
            if ($inboundTransactions->isNotEmpty()) {
                $lastPrice = (float) $inboundTransactions->first()->price;
            }

            // If still 0, maybe check product cost?
            // $product = \App\Models\Product::find($productId);
            // $lastPrice = $product->cost_price ?? 0;

            $value += $remainingQtyToValue * $lastPrice;
        }

        return $value;
    }

    /**
     * Create a Closing Stock financial transaction for the finalized inventory.
     *
     * @param StockInventory $inventory
     * @return ?FinancialTransaction
     */
    public function createClosingStockTransaction(StockInventory $inventory): ?FinancialTransaction
    {
        try {
            // 1. Calculate Total Value
            $amount = $this->calculateClosingStockValue($inventory);

            // 2. Get Closing Stock Category
            $category = FinancialCategory::findByCode(FinancialCategoryCode::CLOSING_STOCK);

            if (!$category) {
                Log::error("Closing Stock Category not found with code: " . FinancialCategoryCode::CLOSING_STOCK);
                return null;
            }

            // 3. Determine Branch
            // Try to find a branch associated with this store
            $branch = Branch::where('store_id', $inventory->store_id)->first();
            $branchId = $branch ? $branch->id : null;

            // 4. Create Transaction
            return FinancialTransaction::create([
                'branch_id' => $branchId,
                'category_id' => $category->id,
                'amount' => $amount,
                'type' => FinancialCategory::TYPE_EXPENSE, // As defined in migration
                'transaction_date' => $inventory->inventory_date,
                'status' => FinancialTransaction::STATUS_PAID, // It's an accounting entry, effectively "paid"/realized
                'description' => "Closing Stock - Inventory #{$inventory->id} - Store: " . ($inventory->store->name ?? 'N/A'),
                'created_by' => auth()->id() ?? $inventory->created_by,
                'reference_type' => StockInventory::class,
                'reference_id' => $inventory->id,
                'month' => \Carbon\Carbon::parse($inventory->inventory_date)->month,
                'year' => \Carbon\Carbon::parse($inventory->inventory_date)->year,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create closing stock transaction for inventory #{$inventory->id}: " . $e->getMessage());
            return null;
        }
    }
}
