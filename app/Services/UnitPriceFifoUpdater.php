<?php

namespace App\Services;

use App\Models\Product;
use App\Models\UnitPrice;
use App\Models\ProductPriceHistory;
use App\Services\FifoMethodService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

                // if (abs($newPrice - $oldPrice) > 0.0001) {
                DB::transaction(function () use ($unitPrice, $newPrice, $oldPrice, $firstAllocation, $sourceModel, &$updated) {
                    // Update price
                    $unitPrice->price = number_format($newPrice, 2, '.', '');
                    $unitPrice->save();

                    ProductPriceHistory::where('product_id', $unitPrice->product_id)
                        ->where('unit_id', $unitPrice->unit_id)
                        ->delete();
                    // Save history
                    ProductPriceHistory::create([
                        'product_id'     => $unitPrice->product_id,
                        'unit_id'        => $unitPrice->unit_id,
                        'old_price'      => $oldPrice,
                        'new_price'      => $newPrice,
                        'source_type'    => $sourceType ?? $firstAllocation['transactionable_type'] ?? null,
                        'source_id'      => $sourceId ?? $firstAllocation['transactionable_id'] ?? null,
                        'note'           => 'Updated based on FIFO from ' . $firstAllocation['transactionable_type']
                            . ' #' . ($firstAllocation['transactionable_id'] ?? 'N/A'),
                        'date'           => $firstAllocation['movement_date'],
                    ]);

                    $updated[] = [
                        'unit_id' => $unitPrice->unit_id,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                    ];
                });
                // }
            }
        }

        return $updated;
    }


    public static function importPricesFromInventoryTransactions($productId): int
    {
        $product = Product::find($productId);
        if (!$product) {
            return 0;
        }

        $count = 0;

        // 🔵 أولًا: جلب الحركات ExcelImport فقط
        $excelImports = \App\Models\InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', 'in')
            ->where('transactionable_type', 'ExcelImport')
            ->orderBy('movement_date', 'ASC')
            ->get();

        foreach ($excelImports as $transaction) {
            foreach ($product->allUnitPrices as $unitPrice) {
                $note = 'Imported from ExcelImport (ID: ' . $transaction->transactionable_id . ')'
                    . ' on ' . $transaction->movement_date;


                $newPrice = ($transaction->price * $unitPrice->package_size) / $transaction->package_size;
                $newPrice = round($newPrice, 2);

                ProductPriceHistory::create([
                    'product_id'  => $productId,
                    'unit_id'     => $unitPrice->unit_id,
                    'old_price'   => null,
                    'new_price'   => $newPrice,
                    'source_type' => $transaction->transactionable_type,
                    'source_id'   => $transaction->transactionable_id,
                    'note'        => $note,
                    'date'        => $transaction->movement_date,
                ]);

                $count++;
            }
        }

        // 🔵 ثم: جلب باقي الحركات (PurchaseInvoice, StockSupplyOrder, GoodsReceivedNote)
        $otherTransactions = \App\Models\InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', 'in')
            ->whereIn('transactionable_type', [
                'App\Models\PurchaseInvoice',
                'App\Models\StockSupplyOrder',
                'App\Models\GoodsReceivedNote',
            ])
            ->orderBy('movement_date', 'ASC')
            ->get();

        foreach ($otherTransactions as $transaction) {
            foreach ($product->allUnitPrices as $unitPrice) {
                $note = 'Imported from ' . class_basename($transaction->transactionable_type)
                    . ' (ID: ' . $transaction->transactionable_id . ')'
                    . ' on ' . $transaction->movement_date;

                $oldPrice = (float) $unitPrice->price;
                $newPrice = ($transaction->price * $unitPrice->package_size) / $transaction->package_size;
                $newPrice = round($newPrice, 2);

                ProductPriceHistory::create([
                    'product_id'  => $productId,
                    'unit_id'     => $unitPrice->unit_id,
                    'old_price'   => $oldPrice,
                    'new_price'   => $newPrice,
                    'source_type' => $transaction->transactionable_type,
                    'source_id'   => $transaction->transactionable_id,
                    'note'        => $note,
                    'date'        => $transaction->movement_date,
                ]);

                $count++;
            }
        }

        return $count;
    }

    public static function updateIfInventoryIsZero(
        $productId,
        $unitId,
        $newPurchasePrice,
        $purchasePackageSize,
        $storeId,
        $date,
        $notes
    ): void {
        $service = new MultiProductsInventoryService(
            null,
            $productId,
            $unitId,
            $storeId
        );

        $inventory = $service->getInventoryForProduct($productId);
        $unitInventory = collect($inventory)->firstWhere('unit_id', $unitId);
        $remainingQty = $unitInventory['remaining_qty'] ?? 0;

        Log::alert('inventory', [$inventory]);
        Log::alert('unitInventory', [$unitInventory]);
        if ($remainingQty <= 0) {
            $pricePerPiece = $newPurchasePrice / $purchasePackageSize;

            $unitPrices = UnitPrice::where('product_id', $productId)->get();

            foreach ($unitPrices as $unitPrice) {
                $newPrice = round($pricePerPiece * $unitPrice->package_size, 2);
                $unitPrice->update([
                    'price' => $newPrice,
                    'date' => $date,
                    'notes' => $notes,
                ]);
            }
        }
    }
}
