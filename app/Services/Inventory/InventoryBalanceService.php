<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryBalanceService
{
    /**
     * جلب رصيد المخزون الحالي للمنتج في المخزن بوحدة الأساس (القطع).
     */
    public function getCurrentBaseBalance(int $productId, int $storeId): float
    {
        return (float) DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->whereNull('deleted_at')
            ->selectRaw("COALESCE(SUM(
                CASE 
                    WHEN movement_type = 'in' THEN (quantity * package_size)
                    WHEN movement_type = 'out' THEN -(quantity * package_size)
                    ELSE 0 
                END
            ), 0) as balance")
            ->value('balance');
    }

    /**
     * حساب الرصيد المتبقي (remaining_quantity) لحركة مخزنية معينة بوحدة الحركة نفسها.
     *
     * يتم استدعاؤها أثناء حدث creating قبل حفظ السجل في قاعدة البيانات،
     * مما يوفر أداءً فائقاً بصفر استعلامات UPDATE إضافية.
     */
    public function calculateRemainingForTransaction(InventoryTransaction $transaction): float
    {
        $productId = (int) $transaction->product_id;
        $storeId   = (int) $transaction->store_id;

        if (! $productId || ! $storeId) {
            return 0.0;
        }

        $packageSize = max((float) ($transaction->package_size ?: 1), 0.000001);
        $quantity    = (float) $transaction->quantity;
        $movementType = strtolower((string) $transaction->movement_type);

        // الرصيد الحالي بوحدة الأساس قبل هذه الحركة
        $currentBaseBalance = $this->getCurrentBaseBalance($productId, $storeId);

        // كمية الحركة الحالية بوحدة الأساس
        $txBaseQty = $quantity * $packageSize;

        // حساب الرصيد الجديد بوحدة الأساس
        if ($movementType === InventoryTransaction::MOVEMENT_IN) {
            $newBaseBalance = $currentBaseBalance + $txBaseQty;
        } elseif ($movementType === InventoryTransaction::MOVEMENT_OUT) {
            $newBaseBalance = $currentBaseBalance - $txBaseQty;
        } else {
            $newBaseBalance = $currentBaseBalance;
        }

        // تحويل الرصيد الجديد إلى وحدة الحركة الحالية
        return round($newBaseBalance / $packageSize, 4);
    }
}
