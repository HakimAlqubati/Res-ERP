<?php

namespace App\Services\Inventory\Optimized\Components;

use App\Models\UnitPrice;
use Illuminate\Support\Collection;

/**
 * InventoryPriceResolver
 * 
 * مسؤول عن حل الأسعار من مصادرها المتعددة
 * 
 * المسؤولية الوحيدة: تحديد السعر الصحيح حسب الأولوية
 * 
 * ترتيب الأولوية:
 * 1. سعر من المخزون للوحدة المحددة
 * 2. حساب من سعر الوحدة الأولى (order = 1)
 * 3. سعر من unit_prices
 */
class InventoryPriceResolver
{
    /**
     * حل السعر من مصادره المتعددة
     * 
     * @return array [price, priceSource, priceStoreId]
     */
    public function resolve(
        UnitPrice $unitPrice,
        Collection $transactionPrices,
        ?UnitPrice $firstUnitPrice,
        float $packageSize
    ): array {
        $unitId = $unitPrice->unit_id;

        // محاولة 1: من المخزون مباشرة للوحدة المحددة
        $transaction = $transactionPrices[$unitId] ?? null;
        if ($transaction) {
            return [
                $transaction->price,
                'inventory',
                $transaction->store_id,
            ];
        }

        // محاولة 2: حساب من الوحدة الأولى (order = 1)
        if ($firstUnitPrice) {
            $firstTransaction = $transactionPrices[$firstUnitPrice->unit_id] ?? null;
            if ($firstTransaction) {
                $basePackageSize = max($firstUnitPrice->package_size ?? 1, 1);
                $calculatedPrice = round(($packageSize / $basePackageSize) * $firstTransaction->price, 2);
                return [
                    $calculatedPrice,
                    'inventory (calculated)',
                    $firstTransaction->store_id,
                ];
            }
        }

        // محاولة 3: من unit_prices
        return [
            $unitPrice->price,
            'unit_price',
            null,
        ];
    }

    /**
     * حساب السعر بناءً على تحويل الوحدات
     */
    public function calculatePriceFromBaseUnit(
        float $basePrice,
        float $basePackageSize,
        float $targetPackageSize
    ): float {
        if ($basePackageSize <= 0) {
            return $basePrice;
        }

        return round(($targetPackageSize / $basePackageSize) * $basePrice, 2);
    }
}
