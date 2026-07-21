<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Mappers;

use App\Models\Product;

final class ProductStockMapper
{
    /**
     * تحويل الرصيد الخام إلى مصفوفة تفصيلية بالوحدات والكميات.
     */
    public function mapToUnits(object $rawStock, int $productId, int $storeId): array
    {
        // 1. حساب الرصيد المتبقي بالوحدة الأساسية (الداخل - الخارج)
        $totalIn  = (float) ($rawStock->total_in ?? 0);
        $totalOut = (float) ($rawStock->total_out ?? 0);
        $remainingBaseQty = $totalIn - $totalOut;

        // 2. جلب المنتج مع وحداته
        $product = Product::with(['reportUnitPrices.unit'])->find($productId);
        
        if (!$product || $product->reportUnitPrices->isEmpty()) {
            return [];
        }

        $unitPrices = $product->reportUnitPrices->sortBy('order');
        $maxOrder = $unitPrices->max('order');
        $maxPackageSize = $unitPrices->max('package_size');

        $result = [];

        // 3. حلقة التكرار لحساب رصيد كل وحدة
        foreach ($unitPrices as $unitPrice) {
            if ($unitPrice->package_size <= 0) continue;

            $packageSize = (float) $unitPrice->package_size;
            $unitId = $unitPrice->unit_id;

            // حساب الكمية المتبقية لهذه الوحدة
            $unitRemainingQty = round($remainingBaseQty / $packageSize, 4);

            $unitRemainingQty = formatQunantity($unitRemainingQty);
            $remainingBaseQty = formatQunantity($remainingBaseQty);
            $result[] = [
                'product_id'              => $product->id,
                'product_code'            => $product->code,
                'product_name'            => $product->name,
                'product_active'          => $product->active,
                
                // بيانات الوحدة
                'unit_id'                 => $unitId,
                'unit_name'               => $unitPrice->unit->name ?? 'N/A',
                'order'                   => $unitPrice->order,
                'package_size'            => $packageSize,
                
                // الكميات
                'remaining_qty'           => $unitRemainingQty,
                'remaining_quantity_base' => $remainingBaseQty,
                'minimum_quantity'        => $unitPrice->minimum_quantity ?? ($unitPrice->order === $maxOrder ? $product->minimum_stock_qty : 0),
                
                // المؤشرات
                'is_last_unit'            => $unitPrice->order === $maxOrder,
                'is_largest_unit'         => $packageSize === $maxPackageSize,
                
                // السعر الأساسي للوحدة (بدون حسابات معقدة من المخزون)
                'price'                   => (float) $unitPrice->price,
            ];
        }

        return $result;
    }
}