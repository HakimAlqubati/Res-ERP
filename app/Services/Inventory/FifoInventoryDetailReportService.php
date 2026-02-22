<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;
use App\Models\Product;
use App\Models\PurchaseInvoice;

class FifoInventoryDetailReportService
{
    public function getDetailedRemainingStock(
        int $productId,
        int $storeId,
        bool $onlySmallestUnit = false
    ): array {
        $product = Product::with('unitPrices.unit')->findOrFail($productId);

        $supplies = InventoryTransaction::query()
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at')
            // ->whereIn('transactionable_type',[
            //     PurchaseInvoice::class
            // ])
            ->orderBy('transaction_date')
            ->get();

        $remainingBatches = [];
        $finalTotalValue = 0;

        foreach ($supplies as $supply) {
            $supplyTotalQty = $supply->quantity * $supply->package_size;

            $consumedQty = InventoryTransaction::query()
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('source_transaction_id', $supply->id)
                ->whereNull('deleted_at')
                ->sum(DB::raw('quantity * package_size'));
            $consumedQty = round($consumedQty, 4);

            // $remainingQty = $supplyTotalQty - $consumedQty;
            // $consumedQty = ceil($consumedQty * 100) / 100;

            $remainingQty = (float) bcsub((string) $supplyTotalQty, (string) $consumedQty, 4);
            $remainingQty = round($remainingQty, 4);


            // if ($remainingQty > 0) {
            $unitBreakdown = [];
            $smallestPackageSize = null;
            if ($onlySmallestUnit) {
                $smallestPackageSize = $product->unitPrices->min('package_size');
            }
            foreach ($product->unitPrices as $unitPrice) {
                if ($unitPrice->package_size > 0) {

                    if ($onlySmallestUnit && $unitPrice->package_size !== $smallestPackageSize) {
                        continue;
                    }
                    $ps = round($unitPrice->package_size, 2);
                    // $convertedQty = formatQunantity($remainingQty / $ps);

                    $convertedQty =  $remainingQty / $ps;

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
                        'consumed_qty' => formatQunantity($consumedQty / $ps),
                        'supply_qty' => formatQunantity($supplyTotalQty / $ps),
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
                'remaining_quantity_standard' => formatQunantity($remainingQty),
                'units_breakdown' => $unitBreakdown,

            ];
            // }
        }
        // dd($remainingBatches);
        return [
            'batches' => $remainingBatches,
            'finalTotalValue' => $onlySmallestUnit ? formatMoneyWithCurrency($finalTotalValue) : null,

        ];
    }
}
