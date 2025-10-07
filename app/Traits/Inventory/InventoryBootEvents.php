<?php
namespace App\Traits\Inventory;

use App\Services\MultiProductsInventoryService;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Log;

trait InventoryBootEvents
{
    protected static function bootInventoryBootEvents()
    {
        // When retrieving the model, modify the `transactionable_type`
        static::retrieved(function ($transaction) {
            if ($transaction->transactionable_type) {
                $transaction->transactionable_type = class_basename($transaction->transactionable_type);
            }
        });
        static::creating(function ($transaction) {

            $product = $transaction->product ?? $transaction->product()->with('supplyOutUnitPrices')->first();

            if (! $product || ! $transaction->unit_id || ! $transaction->quantity) {
                // Log::warning('InventoryTransaction creation skipped due to missing data', [
                //     'product'    => $product ? $product->id : null,
                //     'product_id' => $transaction->product_id ?? null,
                //     'unit_id'    => $transaction->unit_id ?? null,
                //     'quantity'   => $transaction->quantity ?? null,
                // ]);
                return;
            }

            // 1. جلب وحدة الحركة الحالية من unit_prices
            $currentUnitPrice = $product->supplyOutUnitPrices()
                ->where('unit_id', $transaction->unit_id)
                ->first();

            // 2. جلب أصغر وحدة مرتبطة بالمنتج من unit_prices (package_size الأصغر)
            $baseUnitPrice = $product->supplyOutUnitPrices()
                ->orderBy('package_size', 'asc')
                ->first();

            if (! $currentUnitPrice || ! $baseUnitPrice) {
                // Log::warning("Missing unit price mapping", [
                //     'product_id' => $transaction->product_id,
                //     'unit_id'    => $transaction->unit_id,
                // ]);
                return;
            }

            // 3. تعيين الوحدة الأساسية وحجمها
            $transaction->base_unit_id           = $baseUnitPrice->unit_id;
            $transaction->base_unit_package_size = $currentUnitPrice->package_size;

            // 4. حساب الكمية المحوّلة إلى الوحدة الأساسية
            $conversionRate = $currentUnitPrice->package_size / $baseUnitPrice->package_size;

            $res                        = round($transaction->quantity * $conversionRate, 1);
            $transaction->base_quantity = $res;


             // 5. حساب السعر لكل وحدة أساس (بدقة 6 خانات)
            if ($transaction->price && $currentUnitPrice->package_size > 0) {
                $transaction->price_per_base_unit = round(
                    $transaction->price / $currentUnitPrice->package_size,
                    6
                );
            }
            if (is_null($transaction->waste_stock_percentage)) {
                $transaction->waste_stock_percentage = 0;
            }
        });
        static::created(function ($transaction) {

            // 👇 إضافة الهدر المتوقع مباشرة بعد الإدخال
            $wastePercentage = $transaction->waste_stock_percentage ?? 0;

            if ($wastePercentage > 0 && $transaction->movement_type === InventoryTransaction::MOVEMENT_IN) {
                $wasteQuantity = round(($transaction->quantity * $wastePercentage) / 100, 2);

                if ($wasteQuantity > 0) {
                    InventoryTransaction::create([
                        'product_id'           => $transaction->product_id,
                        'movement_type'        => InventoryTransaction::MOVEMENT_OUT,
                        'quantity'             => $wasteQuantity,
                        'unit_id'              => $transaction->unit_id,
                        'movement_date'        => $transaction->transaction_date ?? now(),
                        'package_size'         => $transaction->package_size,
                        'store_id'             => $transaction?->store_id,
                        'price'                => $transaction->price,
                        'transaction_date'     => $transaction->transaction_date ?? now(),
                        'notes'                => 'Auto waste recorded during supply (based on waste percentage: ' . $wastePercentage . '%)',
                        'transactionable_id'   => 0,
                        'transactionable_type' => 'Waste', // رمزي فقط إذا ما عندك جدول
                        'is_waste'             => true,    // إذا كنت أضفت هذا الحقل في المايجريشن
                    ]);
                }
            }
            // update unit prices
            if ($transaction->movement_type === InventoryTransaction::MOVEMENT_OUT) {
                // UnitPriceFifoUpdater::updatePriceUsingFifo(
                //     $transaction->product_id,
                //     $transaction
                // );
            }

            $availableQty = MultiProductsInventoryService::getRemainingQty(
                $transaction->product_id,
                $transaction->unit_id,
                $transaction->store_id
            );
            $transaction->remaining_quantity = $availableQty;
             $transaction->save();
        });
    }
}