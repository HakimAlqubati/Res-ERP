<?php

namespace App\Services\Orders\Reports;

use App\Models\Order;
use App\Models\OrderDetails;

class ReorderDueToStockReportService
{
    public function getReorderDueToStockReport($groupByOrder)
    {
        $result = [];
        $orderDetails = OrderDetails::with(['order', 'product', 'unit'])
            ->where('is_created_due_to_qty_preivous_order', 1)
            ->whereHas('order', function ($query) {
                $query->where('status', Order::PENDING_APPROVAL);
            })
            ->get();

        if ($groupByOrder) {
            // ✅ نجمع حسب المنتج + الوحدة عبر مفتاح مشترك
            $grouped = $orderDetails->groupBy(function ($detail) {
                return $detail->product_id . '-' . $detail->unit_id;
            });


            foreach ($grouped as $key => $items) {
                $first = $items->first();

                $result[] = [
                    'order_detail_id' => $first->id, // فقط أول ID، لو مهم التتبع
                    'order_id' => $first->order_id, // أول Order ID فقط (للتناسق)
                    'product_id' => $first->product_id,
                    'product_code' => $first->product?->code,
                    'product_name' => $first->product?->name,
                    'unit_id' => $first->unit_id,
                    'unit_name' => $first->unit?->name,
                    'quantity' => $items->sum('quantity'), // ✅ نجمع الكميات
                ];
            }
        } else {
            // 🧩 بدون تجميع، ترجع التفاصيل العادية
            $result = $orderDetails->map(function ($detail) {
                return [
                    'order_detail_id' => $detail->id,
                    'order_id' => $detail->order_id,
                    'product_id' => $detail->product_id,
                    'product_code' => $detail->product?->code,
                    'product_name' => $detail->product?->name,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit?->name,
                    'quantity' => $detail->quantity,
                ];
            });
        }
        return $result;
    }
}
