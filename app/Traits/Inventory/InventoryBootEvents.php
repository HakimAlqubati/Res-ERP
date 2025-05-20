<?php

namespace App\Traits\Inventory;

use App\Models\InventoryTransaction;
use App\Services\UnitPriceFifoUpdater;

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
                    \App\Models\InventoryTransaction::create([
                        'product_id' => $transaction->product_id,
                        'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                        'quantity' => $wasteQuantity,
                        'unit_id' => $transaction->unit_id,
                        'movement_date' => $transaction->transaction_date ?? now(),
                        'package_size' => $transaction->package_size,
                        'store_id' => $transaction?->store_id,
                        'price' => $transaction->price,
                        'transaction_date' => $transaction->transaction_date ?? now(),
                        'notes' => 'Auto waste recorded during supply (based on waste percentage: ' . $wastePercentage . '%)',
                        'transactionable_id' => 0,
                        'transactionable_type' => 'Waste', // رمزي فقط إذا ما عندك جدول
                        'is_waste' => true, // إذا كنت أضفت هذا الحقل في المايجريشن
                    ]);
                }
            }
            // update unit prices
            if ($transaction->movement_type === InventoryTransaction::MOVEMENT_OUT) {
                UnitPriceFifoUpdater::updatePriceUsingFifo(
                    $transaction->product_id,
                    $transaction
                );
            }
        });
    }
}
