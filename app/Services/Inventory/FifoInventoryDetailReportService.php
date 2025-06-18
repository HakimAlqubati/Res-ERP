<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;
use App\Models\Product;

class FifoInventoryDetailReportService
{
    public function getDetailedRemainingStock(int $productId, int $storeId): array
    {
        $product = Product::with('unitPrices.unit')->findOrFail($productId);

        $supplies = InventoryTransaction::query()
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at')
            ->orderBy('transaction_date')
            ->get();

        $remainingBatches = [];

        foreach ($supplies as $supply) {
            $supplyTotalQty = $supply->quantity * $supply->package_size;

            $consumedQty = InventoryTransaction::query()
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('source_transaction_id', $supply->id)
                ->whereNull('deleted_at')
                ->sum(DB::raw('quantity * package_size'));

            $remainingQty = $supplyTotalQty - $consumedQty;

            if ($remainingQty > 0) {
                $unitBreakdown = [];

                foreach ($product->unitPrices as $unitPrice) {
                    if ($unitPrice->package_size > 0) {
                        $convertedQty = round($remainingQty / $unitPrice->package_size, 2);
                        $convertedPrice = round(($supply->price * $unitPrice->package_size) / $supply->package_size, 4);
                        $totalValue = round($convertedQty * $convertedPrice, 4);

                        $unitBreakdown[] = [
                            'unit_id' => $unitPrice->unit_id,
                            'unit_name' => $unitPrice->unit->name,
                            'package_size' => $unitPrice->package_size,
                            'remaining_quantity' => $convertedQty,
                            'price' => $convertedPrice,
                            'total_value' => $totalValue,
                        ];
                    }
                }

                $remainingBatches[] = [
                    'transaction_id' => $supply->id,
                    'transactionable_type' => $supply->transactionable_type,
                    'transactionable_id' => $supply->transactionable_id,
                    'transaction_date' => date('Y-m-d', strtotime($supply->transaction_date)),
                    'original_price' => $supply->price,
                    'store_id' => $supply->store_id,
                    'remaining_quantity_standard' => round($remainingQty, 4),
                    'units_breakdown' => $unitBreakdown,
                ];
            }
        }

        return $remainingBatches;
    }
}
