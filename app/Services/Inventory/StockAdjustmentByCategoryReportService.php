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

        $result = $adjustments
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
                                        $unitPrice = $item->inventoryTransaction->price;
                                        $price = formatMoneyWithCurrency(
                                            ($unitPrice ?? 0) * ($item->quantity ?? 0)
                                        );
                                        return [
                                            'product' => $item->product->name ?? 'Unknown Product',
                                            'quantity' => formatQunantity($item->quantity),
                                            'unit_price' =>  formatMoneyWithCurrency($unitPrice),
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
                                                'price' => formatMoneyWithCurrency($unitPrice),
                                            ] : null,
                                        ];
                                    })->values();
                                }

                                $totalPrice = $storeGroup->sum(function ($item) {
                                    return ($item->inventoryTransaction?->price ?? 0) * ($item->quantity ?? 0);
                                });

                                $entry['total_price'] = formatMoneyWithCurrency($totalPrice);

                                return $entry;
                            });
                    });
            })->values();

        // ✅ Add final total price if adjustment_type is filtered
        if ($adjustmentType) {
            $finalTotalPrice = $adjustments->sum(function ($item) {
                return ($item->inventoryTransaction?->price ?? 0) * ($item->quantity ?? 0);
            });

            $result->push([
                'final_total_price' => formatMoneyWithCurrency($finalTotalPrice),
            ]);
        }

        // ✅ إذا "ما في" adjustmentType: نحسب Final Net Total بإشارة النوع
        $signedTotals = $adjustments->reduce(function ($carry, $item) {
            $unit = $item->inventoryTransaction?->price ?? 0;
            $qty  = $item->quantity ?? 0;
            $line = $unit * $qty;

            $sign = self::signForAdjustmentType($item->adjustment_type);

            if ($sign > 0) {
                $carry['increase'] += $line;
            } elseif ($sign < 0) {
                $carry['decrease'] += $line; // نتركه موجب هنا ثم نوقّعه عند العرض
            } else {
                $carry['other'] += $line;    // لأنواع غير معروفة
            }

            return $carry;
        }, ['increase' => 0.0, 'decrease' => 0.0, 'other' => 0.0]);

        $net = $signedTotals['increase'] - $signedTotals['decrease'] + $signedTotals['other'];

        // نضيف سطر تلخيصي واضح والقيم موقّعة كما طلبت
        $result->push([
            'final_total_price'   => formatMoneyWithCurrency($net),                      // الصافي (increase - decrease + other)
            'increase_total'      => formatMoneyWithCurrency($signedTotals['increase']), // موجب
            'decrease_total'      => formatMoneyWithCurrency(-1 * $signedTotals['decrease']), // سالب
            'other_total'         => $signedTotals['other'] === 0 ? null : formatMoneyWithCurrency($signedTotals['other']),
        ]);
        return $result;
    }

    /**
     * إرجاع إشارة النوع: increase = +1, decrease = -1, غير ذلك = 0
     */
    protected static function signForAdjustmentType(?string $type): int
    {
        $t = strtolower((string) $type);

        // غطّيت تسميات شائعة محتملة. عدّل حسب نظامك/Enums لديك.
        $positive = ['increase', 'increment', 'in', 'plus', 'gain', 'addition', 'add'];
        $negative = ['decrease', 'decrement', 'out', 'minus', 'loss', 'write_off', 'writeoff', 'shrinkage'];

        if (in_array($t, $positive, true)) {
            return 1;
        }
        if (in_array($t, $negative, true)) {
            return -1;
        }
        return 0;
    }
}
