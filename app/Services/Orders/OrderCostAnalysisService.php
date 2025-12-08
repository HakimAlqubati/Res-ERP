<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\InventoryTransaction;
use App\Models\OrderDetails;
use App\Models\Branch; // تم تضمينه لجلب معلومات الفرع
use Illuminate\Support\Facades\DB;

class OrderCostAnalysisService
{
    /**
     * الحالات التي تعتبر فيها تكلفة الطلب محققة وجاهزة للتحليل.
     */
    protected const VALID_STATUSES = [
        Order::READY_FOR_DELEVIRY,
        Order::DELEVIRED,
    ];

    /**
     * يستقبل رقم الطلب ويحسب إجمالي القيمة (سعر البيع) وإجمالي التكلفة (سعر التكلفة).
     *
     * @param int $orderId رقم الطلب
     * @return array<string, float|null|string> مصفوفة تحتوي على نتائج التحليل.
     */
    public function getOrderValues(int $orderId): array
    {
        // 1. استرجاع الطلب مع تحميل علاقة الفرع ومخزن الفرع
        $order = Order::query()
            ->where('id', $orderId)
            ->with('branch') // تحميل الفرع
            ->first();

        if (!$order) {
            return $this->errorResponse($orderId, 'Order not found.');
        }

        // --- أولا: التحقق من صلاحية الطلب ---

        // 1.1 التحقق من حالة الطلب
        if (!in_array($order->status, self::VALID_STATUSES)) {
            return $this->errorResponse($orderId, 'Order status is not suitable for cost analysis (must be Ready for Delivery or Delivered).');
        }

        // 1.2 التحقق من إلغاء الطلب
        if ($order->cancelled) {
            return $this->errorResponse($orderId, 'Order has been cancelled.');
        }

        // 2. حساب إجمالي قيمة الطلب (سعر البيع/التحويل)
        $orderValue = OrderDetails::query()
            ->where('order_id', $orderId)
            ->sum(
                DB::raw('price * available_quantity')
            );

        // --- ثانياً وثالثاً: حساب التكلفة والتحقق من حركات المخزون ---
        $branchStoreId = $order->branch?->store_id;

        // 3.1 حساب إجمالي تكلفة المخزون الصادرة (COGS)
        $inventoryCostQuery = InventoryTransaction::query()
            ->where('transactionable_type', Order::class)
            ->where('transactionable_id', $orderId)
            ->where('store_id', $branchStoreId)
            // الحركة الصادرة تمثل التكلفة المحققة (COGS) من المخزن المركزي
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN);

        // حساب التكلفة مع استخدام unit_prices كبديل إذا كان السعر صفر أو فارغ
        $inventoryCost = (clone $inventoryCostQuery)
            ->leftJoin('unit_prices', function ($join) {
                $join->on('inventory_transactions.product_id', '=', 'unit_prices.product_id')
                    ->on('inventory_transactions.unit_id', '=', 'unit_prices.unit_id');
            })
            ->sum(DB::raw('inventory_transactions.quantity * COALESCE(NULLIF(inventory_transactions.price, 0), unit_prices.price, 0)'));

        // 3.2 التحقق من وجود حركات مخزون صادرة
        if ($inventoryCost == 0 && $inventoryCostQuery->doesntExist()) {
            return $this->errorResponse($orderId, 'No corresponding MOVEMENT_OUT Inventory Transactions found (COGS not recorded).');
        }


        if ($branchStoreId) {
            // التحقق من وجود حركات واردة إلى مخزن الفرع
            $branchInMovementExists = InventoryTransaction::query()
                ->where('transactionable_type', Order::class)
                ->where('transactionable_id', $orderId)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->where('store_id', $branchStoreId)
                ->exists();

            if (!$branchInMovementExists) {
                // ملاحظة: هذا التحقق قد يكون اختياريًا، بعض الأنظمة تسجل COGS فقط.
                // لكن لتلبية الطلب، نتحقق.
                $notes = 'COGS recorded, but no corresponding MOVEMENT_IN found for branch store ID ' . $branchStoreId;
            } else {
                $notes = 'COGS recorded and MOVEMENT_IN verified for branch store ID ' . $branchStoreId;
            }
        } else {
            $notes = 'COGS recorded. The order is not associated with a branch store or branch store ID is missing.';
        }


        // 5. إرجاع النتائج
        return [
            'order_id' => $orderId,
            'status' => $order->status,
            'branch_store_id' => $branchStoreId,
            'total_amount_from_order_details' => round($orderValue, 2),
            'total_cost_from_inventory_transactions' => round($inventoryCost, 2),
            'message' => 'Analysis complete.',
            'notes' => $notes,
        ];
    }

    /**
     * دالة مساعدة لإنشاء استجابة الخطأ الموحدة.
     *
     * @param int $orderId
     * @param string $errorMessage
     * @return array
     */
    protected function errorResponse(int $orderId, string $errorMessage): array
    {
        return [
            'order_id' => $orderId,
            'status' => 'Error',
            'branch_store_id' => null,
            'total_amount_from_order_details' => null,
            'total_cost_from_inventory_transactions' => null,
            'message' => $errorMessage,
            'notes' => 'Analysis failed due to error.',
        ];
    }
}
