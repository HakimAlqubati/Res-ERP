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
     * @param array|null $categoryIds
     * @param bool $withDetails
     * @return Collection
     */
    public function generate(
        ?string $adjustmentType = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $storeId = null,
        ?array $categoryIds = null,
        bool $withDetails = false
    ): Collection {
        $query = StockAdjustmentDetail::with(['product.category', 'store', 'inventoryTransaction'])
            ->whereHas('inventoryTransaction')
            ->when($adjustmentType, fn($q) => $q->where('adjustment_type', $adjustmentType))
            ->when($fromDate, fn($q) => $q->whereDate('adjustment_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('adjustment_date', '<=', $toDate))
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        if (is_array($categoryIds) && count($categoryIds) > 0) {
            $query->whereHas('product.category', fn($q) => $q->whereIn('id', $categoryIds));
        }

        $adjustments = $query->get();

        return $adjustments
            ->groupBy(fn($item) => $item->product->category_id ?? 0)
            ->flatMap(function ($categoryGroup, $categoryId) use ($withDetails) {
                $categoryName = optional($categoryGroup->first()?->product?->category)->name ?? 'Without Category';

                return $categoryGroup
                    ->groupBy(fn($item) => $item->adjustment_type)
                    ->flatMap(function ($typeGroup, $type) use ($categoryId, $categoryName, $withDetails) {
                        return $typeGroup
                            ->groupBy(fn($item) => $item->store->id ?? 0)
                            ->map(function ($storeGroup, $storeId) use ($categoryId, $categoryName, $type, $withDetails) {
                                $storeName = optional($storeGroup->first()?->store)->name ?? 'Unknown Store';

                                $entry = [
                                    'category_id'     => $categoryId,
                                    'category'        => $categoryName,
                                    'adjustment_type' => $type,
                                    'store_id'        => $storeId,
                                    'store'           => $storeName,
                                    'product_count'   => $storeGroup->pluck('product_id')->unique()->count(),
                                ];

                                if ($withDetails) {
                                    $entry['adjustments'] = $storeGroup->map(function ($item) {
                                        $price = formatMoneyWithCurrency($item->inventoryTransaction->price ?? 0);

                                        return [
                                            'product' => $item->product->name ?? 'Unknown Product',
                                            'quantity' => formatQunantity($item->quantity),
                                            'unit' => $item->unit->name ?? null,
                                            'notes' => $item->notes,
                                            'date' => $item->adjustment_date,
                                            'price' => $price,
                                            'inventory_transaction' => $item->inventoryTransaction ? [
                                                'id' => $item->inventoryTransaction->id,
                                                'type' => $item->inventoryTransaction->type,
                                                'reference' => $item->inventoryTransaction->reference,
                                                'quantity' => $item->inventoryTransaction->quantity,
                                                'created_at' => $item->inventoryTransaction->created_at,
                                                'price' => $item->inventoryTransaction->price,
                                            ] : null,
                                        ];
                                    })->values();
                                }

                                $totalPrice = $storeGroup
                                    ->pluck('inventoryTransaction')
                                    ->filter()
                                    ->sum(fn($tx) => $tx?->price ?? 0);

                                $entry['total_price'] = formatMoneyWithCurrency($totalPrice);

                                return $entry;
                            });
                    });
            })->values();
    }
}