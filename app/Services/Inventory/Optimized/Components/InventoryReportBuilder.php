<?php

namespace App\Services\Inventory\Optimized\Components;

use App\Models\Product;

/**
 * InventoryReportBuilder
 * 
 * مسؤول عن بناء تقارير المخزون من البيانات المحملة
 * 
 * المسؤولية الوحيدة: تحويل البيانات الخام إلى تقارير منسقة
 */
class InventoryReportBuilder
{
    private InventoryDataLoader $dataLoader;
    private InventoryPriceResolver $priceResolver;

    public function __construct(
        InventoryDataLoader $dataLoader,
        InventoryPriceResolver $priceResolver
    ) {
        $this->dataLoader = $dataLoader;
        $this->priceResolver = $priceResolver;
    }

    /**
     * بناء التقرير لقائمة منتجات
     */
    public function buildForProducts(array $productIds): array
    {
        $report = [];
        foreach ($productIds as $productId) {
            $report[] = $this->buildForSingleProduct($productId);
        }
        return $report;
    }

    /**
     * بناء تقرير المخزون لمنتج واحد
     */
    public function buildForSingleProduct(int $productId): array
    {
        $product = $this->dataLoader->getProduct($productId);
        if (!$product) {
            return [];
        }

        $unitPrices = $this->dataLoader->getUnitPricesForProduct($productId);
        if ($unitPrices->isEmpty()) {
            return [];
        }

        // حساب الكميات الإجمالية
        $totals = $this->dataLoader->getInventoryTotalsForProduct($productId);
        $quantities = $this->calculateQuantities($totals);

        // تحديد الوحدة الأساسية والحدود القصوى
        $baseUnitPrice = $unitPrices->sortBy('package_size')->first();
        $maxOrder = $unitPrices->max('order');
        $maxPackageSize = $unitPrices->max('package_size');

        // آخر أسعار من المخزون
        $transactionPrices = $this->dataLoader->getTransactionPricesForProduct($productId);
        $firstUnitPrice = $unitPrices->firstWhere('order', 1);

        return $this->buildProductItems(
            $product,
            $unitPrices,
            $quantities,
            $baseUnitPrice,
            $maxOrder,
            $maxPackageSize,
            $transactionPrices,
            $firstUnitPrice
        );
    }

    /**
     * حساب الكميات من الإجماليات
     */
    private function calculateQuantities(?object $totals): array
    {
        $totalIn = (float) ($totals->total_in ?? 0);
        $totalOut = (float) ($totals->total_out ?? 0);
        $totalBaseIn = (float) ($totals->total_base_in ?? 0);
        $totalBaseOut = (float) ($totals->total_base_out ?? 0);

        return [
            'remaining' => $totalIn - $totalOut,
            'remaining_base' => round($totalBaseIn - $totalBaseOut, 4),
        ];
    }

    /**
     * بناء عناصر المنتج لكل وحدة
     */
    private function buildProductItems(
        Product $product,
        $unitPrices,
        array $quantities,
        $baseUnitPrice,
        ?int $maxOrder,
        ?float $maxPackageSize,
        $transactionPrices,
        $firstUnitPrice
    ): array {
        $result = [];
        $productId = $product->id;

        foreach ($unitPrices as $unitPrice) {
            $packageSize = $unitPrice->package_size;

            if ($packageSize <= 0) {
                continue;
            }

            // حساب الكميات المتبقية لهذه الوحدة
            $remainingQty = round($quantities['remaining'] / $packageSize, 4);
            $remainingBaseQty = round($quantities['remaining_base'] / $packageSize, 4);

            // تحديد الحد الأدنى (فقط لآخر وحدة)
            $minimumQty = ($unitPrice->order == $maxOrder)
                ? ($product->minimum_stock_qty ?? 0)
                : 0;

            // حل السعر
            [$price, $priceSource, $priceStoreId] = $this->priceResolver->resolve(
                $unitPrice,
                $transactionPrices,
                $firstUnitPrice,
                $packageSize
            );

            $result[] = [
                'product_id' => $productId,
                'product_active' => $product->active,
                'product_code' => $product->code,
                'product_name' => $product->name,
                'unit_id' => $unitPrice->unit_id,
                'order' => $unitPrice->order,
                'package_size' => $packageSize,
                'unit_name' => $unitPrice->unit->name ?? 'Unknown',
                'remaining_qty' => $remainingQty,
                'remaining_quantity_base' => $remainingBaseQty,
                'base_unit_id' => $baseUnitPrice?->unit_id,
                'base_unit_name' => $baseUnitPrice?->unit?->name,
                'minimum_quantity' => $minimumQty,
                'is_last_unit' => $unitPrice->order == $maxOrder,
                'is_largest_unit' => $unitPrice->package_size == $maxPackageSize,
                'price' => $price,
                'price_source' => $priceSource,
                'price_store_id' => $priceStoreId,
            ];
        }

        return $result;
    }

    /**
     * فلترة المنتجات تحت الحد الأدنى
     */
    public function filterBelowMinimum(array $report, bool $checkLastUnit = true, bool $checkLargestUnit = false): array
    {
        $lowStock = [];

        foreach ($report as $productData) {
            foreach ($productData as $item) {
                $isTargetUnit = ($checkLastUnit && $item['is_last_unit'])
                    || ($checkLargestUnit && $item['is_largest_unit']);

                if ($isTargetUnit && $item['remaining_qty'] <= $item['minimum_quantity']) {
                    $lowStock[] = $item;
                }
            }
        }

        return $lowStock;
    }

    /**
     * فلترة المنتجات المتوفرة فقط
     */
    public function filterAvailableOnly(array $report): array
    {
        $available = [];

        foreach ($report as $productData) {
            $totalRemaining = collect($productData)->sum('remaining_qty');
            if ($totalRemaining > 0) {
                $available[] = $productData;
            }
        }

        return $available;
    }
}
