<?php

namespace App\Services;

use App\Models\Product;
use App\Models\UnitPrice;
use App\Models\ProductPriceHistory;
use App\Services\FifoMethodService;
use Illuminate\Support\Facades\DB;

class UnitPriceFifoUpdater
{
    public static function updatePriceUsingFifo($productId, $sourceModel = null): array
    {
        $product = Product::find($productId);
        if (!$product) {
            return [];
        }

        $unitPrices = $product->allUnitPrices;
        $updated = [];

        foreach ($unitPrices as $unitPrice) {
            $fifoService = new FifoMethodService();

            $allocations = $fifoService->getAllocateFifo(
                $productId,
                $unitPrice->unit_id,
                0.0000001
            );

            $firstAllocation = collect($allocations)->first();

            if ($firstAllocation && isset($firstAllocation['price_based_on_unit'])) {
                // ⚠️ نزيل الفاصلة من السعر إذا كانت موجودة
                $rawNewPrice = str_replace(',', '', $firstAllocation['price_based_on_unit']);
                $newPrice = (float) $rawNewPrice;
                $oldPrice = (float) $unitPrice->price;

                if (abs($newPrice - $oldPrice) > 0.0001) {
                    DB::transaction(function () use ($unitPrice, $newPrice, $oldPrice, $firstAllocation, $sourceModel, &$updated) {
                        // Update price
                        $unitPrice->price = number_format($newPrice, 2, '.', '');
                        $unitPrice->save();

                        // Save history
                        ProductPriceHistory::create([
                            'product_id'     => $unitPrice->product_id,
                            'unit_id'        => $unitPrice->unit_id,
                            'old_price'      => $oldPrice,
                            'new_price'      => $newPrice,
                            'source_type'    => $sourceModel ? get_class($sourceModel) : null,
                            'source_id'      => $sourceModel?->id,
                            'note'           => 'Updated based on FIFO from Invoice #' . ($firstAllocation['transactionable_id'] ?? 'N/A'),
                        ]);

                        $updated[] = [
                            'unit_id' => $unitPrice->unit_id,
                            'old_price' => $oldPrice,
                            'new_price' => $newPrice,
                        ];
                    });
                }
            }
        }

        return $updated;
    }
}
