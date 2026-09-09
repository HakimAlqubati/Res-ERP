<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Contracts;

use Illuminate\Database\Eloquent\Model;

interface FifoAllocatorInterface
{
    /**
     * تخصيص كمية من المخزون حسب FIFO.
     *
     * يُرجع مصفوفة allocations بنفس الهيكل المتوقع من FifoMethodService::getAllocateFifo()
     * لضمان التوافق مع Order::moveFromInventory() والأماكن الأخرى.
     *
     * @return array<int, array{
     *     transaction_id: int,
     *     store_id: int,
     *     unit_id: int,
     *     target_unit_id: int,
     *     target_unit_package_size: float,
     *     entry_price: float,
     *     price_based_on_unit: float,
     *     package_size: float,
     *     movement_date: string,
     *     transactionable_id: int,
     *     transactionable_type: string,
     *     entry_qty: float,
     *     entry_qty_based_on_unit: float,
     *     remaining_qty_based_on_unit: float,
     *     notes: string,
     *     deducted_qty: float,
     *     previous_ordered_qty_based_on_unit: float,
     *     source_order_id: int|null,
     * }>
     */
    public function allocate(
        int $productId,
        int $unitId,
        float $requestedQty,
        int $storeId,
        ?Model $sourceModel = null
    ): array;

    /**
     * التحقق من أن الكمية المطلوبة متوفرة في المخزون.
     */
    public function hasEnoughStock(
        int $productId,
        int $unitId,
        float $requestedQty,
        int $storeId
    ): bool;

    /**
     * جلب الرصيد المتاح لمنتج بوحدة معينة في مخزن معين.
     */
    public function getAvailableQty(
        int $productId,
        int $unitId,
        int $storeId
    ): float;

    /**
     * تخصيص كميات لعدة منتجات دفعة واحدة (استعلام SQL واحد).
     *
     * @param  array<int, array{product_id: int, unit_id: int, qty: float}>  $items
     * @return array<int, array{status: string, allocations?: array, message?: string}>
     */
    public function allocateMany(
        array $items,
        int $storeId,
        ?Model $sourceModel = null
    ): array;
}
