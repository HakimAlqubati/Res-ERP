<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ManufacturingInventoryDetailReportService
{
    public function getDetailedRemainingStock(int $productId, int $storeId, bool $onlySmallestUnit = false): array
    {
        $product = Product::with('unitPrices.unit')->findOrFail($productId);

        // الحركات الداخلة من الطلبات
        $supplies = InventoryTransaction::query()
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where('transactionable_type', 'App\Models\Order')
            ->whereNull('deleted_at')
            ->orderBy('transaction_date')
            ->get();

        $remainingBatches = [];
        $finalTotalValue = 0;

        foreach ($supplies as $supply) {
            $supplyTotalQty = $supply->quantity * $supply->package_size;

            // الاستهلاك من خلال عمليات التصنيع
            $consumedQty = InventoryTransaction::query()
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('product_id', $productId)
                ->where('store_id', $storeId)
                ->where('source_transaction_id', $supply->id)
                ->where('transactionable_type', 'App\Models\StockSupplyOrder')
                ->whereNull('deleted_at')
                ->sum(DB::raw('quantity * package_size'));
            // dd($consumedQty);

            $consumedQty = round($consumedQty, 2);
            $remainingQty = round($supplyTotalQty - $consumedQty, 2);

            // if ($remainingQty > 0) {
            $unitBreakdown = [];
            $smallestPackageSize = $onlySmallestUnit ? $product->unitPrices->min('package_size') : null;

            foreach ($product->unitPrices as $unitPrice) {
                if ($unitPrice->package_size > 0) {
                    if ($onlySmallestUnit && $unitPrice->package_size !== $smallestPackageSize) {
                        continue;
                    }

                    $convertedQty = $remainingQty / $unitPrice->package_size;
                    $convertedPrice = round(($supply->price * $unitPrice->package_size) / $supply->package_size, 2);
                    $totalValue = round($convertedQty * $convertedPrice, 2);
                    $finalTotalValue += $totalValue;

                    $unitBreakdown[] = [
                        'unit_id' => $unitPrice->unit_id,
                        'unit_name' => $unitPrice->unit->name,
                        'package_size' => $unitPrice->package_size,
                        'remaining_quantity' => formatQunantity($convertedQty),
                        'price' => formatMoneyWithCurrency($convertedPrice),
                        'total_value' => formatMoneyWithCurrency($totalValue),
                    ];
                }
            }

            $remainingBatches[] = [
                'transaction_id' => $supply->transactionable_id,
                'transaction_date' => date('Y-m-d', strtotime($supply->transaction_date)),
                'original_price' => $supply->price,
                'store_id' => $supply->store_id,
                'remaining_quantity_standard' => formatQunantity($remainingQty),
                'units_breakdown' => $unitBreakdown,
                'source_type' => 'Order',
            ];
            // }
        }

        return [
            'batches' => $remainingBatches,
            'finalTotalValue' => $onlySmallestUnit ? formatMoneyWithCurrency($finalTotalValue) : null,
        ];
    }
}
