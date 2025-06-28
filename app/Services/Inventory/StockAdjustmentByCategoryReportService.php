<?php

namespace App\Services\Inventory;

use App\Models\StockAdjustmentDetail;
use Illuminate\Support\Collection;

class StockAdjustmentByCategoryReportService
{
    /**
     * Generate summary report of stock adjustments by product category and adjustment type.
     *
     * @param string|null $adjustmentType
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param int|null $storeId
     * @return Collection
     */
    public function generate(
        ?string $adjustmentType = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $storeId = null,
        ?array $categoryIds = null
    ): Collection {
        $query = StockAdjustmentDetail::with(['product.category', 'store'])
            ->when($adjustmentType, fn($q) => $q->where('adjustment_type', $adjustmentType))
            ->when($fromDate, fn($q) => $q->whereDate('adjustment_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('adjustment_date', '<=', $toDate))
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($categoryIds, function ($q) use ($categoryIds) {
                $q->whereHas('product.category', fn($query) => $query->whereIn('id', $categoryIds));
            });

        $adjustments = $query->get();

        // Group by category, adjustment type, and store
        return $adjustments->groupBy(function ($item) {
            return $item->product->category->name ?? 'Without Category';
        })->flatMap(function ($categoryGroup, $categoryName) {
            return $categoryGroup
                ->groupBy(fn($item) => $item->adjustment_type)
                ->flatMap(function ($typeGroup, $type) use ($categoryName) {
                    return $typeGroup
                        ->groupBy(fn($item) => $item->store->name ?? 'Unknown Store')
                        ->map(function ($storeGroup, $storeName) use ($categoryName, $type) {
                            return [
                                'category' => $categoryName,
                                'adjustment_type' => $type,
                                'product_count' => $storeGroup->pluck('product_id')->unique()->count(),
                                'store' => $storeName,
                            ];
                        });
                });
        })->values();
    }
}