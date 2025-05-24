<?php

namespace App\Services\FixFifo;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Unit;
use App\Models\UnitPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FifoAllocatorService
{
    public function allocate(int $productId): array
    {
        $units  = Unit::active()->get(['id', 'name'])->keyBy('id');
        $unitPrices = DB::table('unit_prices')
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->where('show_in_invoices', 1)
            ->get(['unit_id', 'package_size'])
            ->keyBy('package_size');
        // Step 1: Get supply (movement_type = 'in') ordered by transaction_date
        $supplies = DB::table('inventory_transactions')
            ->where('movement_type', 'in')
            ->where('product_id', $productId)
            ->orderBy('id')
            ->whereNull('deleted_at')
            ->get([
                'id',
                'quantity',
                'package_size',
                'unit_id',
                'store_id',
                'price',
                'transaction_date',
                'transactionable_id',
                'transactionable_type'
            ]);

        // Step 2: Get orders details (delivered or ready) grouped by branch & unit
        $orders = DB::table('orders_details as od')
            ->join('orders as o', 'od.order_id', '=', 'o.id')
            ->where('od.product_id', $productId)
            ->whereIn('o.status', ['ready_for_delivery', 'delevired'])
            ->whereNull('o.deleted_at')
            ->orderBy('o.id') // order matters for FIFO matching
            ->get([
                'o.branch_id',
                'od.unit_id',
                'od.package_size',
                'od.available_quantity',
                'od.order_id',
                'o.created_at',
            ]);

        $issueOrders = DB::table('stock_issue_order_details as sid')
            ->join('stock_issue_orders as si', 'sid.stock_issue_order_id', '=', 'si.id')
            ->where('sid.product_id', $productId)

            ->whereNull('si.deleted_at')
            ->orderBy('si.id')
            ->get([
                'sid.unit_id',
                'sid.package_size',
                'sid.quantity as available_quantity',
                'sid.stock_issue_order_id as order_id',
                'si.created_at',
                DB::raw("'stock_issue' as source_type")
            ]);
        $allocations = [];
        $allOrders = $orders->merge($issueOrders);
        $allOrders = $allOrders->sortBy('created_at')->values();
        foreach ($allOrders as $order) {
            // $transactionableType = isset($order->stock_issue_order_id) ? \App\Models\StockIssueOrder::class : \App\Models\Order::class;
            $transactionableType = isset($order->source_type) && $order->source_type === 'stock_issue'
                ? \App\Models\StockIssueOrder::class
                : \App\Models\Order::class;
            $targetUnit = \App\Models\UnitPrice::where('product_id', $productId)
                ->where('unit_id', $order->unit_id)->with('unit')
                ->first();
            // حساب الكمية المطلوبة فعليًا من الطلب بناءً على المتاح × حجم العبوة
            $remainingQty = $order->available_quantity * $order->package_size;

            // المرور على كل توريد (supply) بالترتيب لتطبيق تخصيص FIFO
            foreach ($supplies as $key => $supply) {
                // حساب الكمية المتبقية فعليًا في هذا التوريد
                $supplyAvailable = $supply->quantity * $supply->package_size;

                // إذا كانت الكمية صفر أو أقل، تجاهل هذا التوريد
                if ($supplyAvailable <= 0) continue;

                // تحديد الكمية التي يمكن تخصيصها من هذا التوريد لهذا الطلب
                $allocatedQty = min($remainingQty, $supplyAvailable);
                $orderedPrice = ($supply->price * $order->package_size) / $supply->package_size;
                if (!$targetUnit) {
                    continue;
                }
                // حفظ سجل التخصيص كـ حركة مخزون OUT (جاهزة للحفظ في جدول inventory_transactions)
                $quantity = round($allocatedQty / $targetUnit->package_size, 2);
                if ($quantity <= 0) {
                    continue;
                }
                $allocations[] = [
                    'order_id'              => $order->order_id,                    // الطلب الذي تم تخصيص الكمية له
                    'product_id'            => $productId,                           // معرف المنتج
                    'unit_id'               => $targetUnit->unit_id,                    // وحدة المنتج
                    'unit' =>               $targetUnit->unit->name,
                    'quantity'              => $quantity,
                    'package_size'          => $order->package_size,              // حجم العبوة للتوريد المستخدم
                    'package_size_supply' => $supply->package_size,
                    'store_id'              => $supply->store_id,                  // المخزن الذي خرجت منه الكمية
                    'price'                 => $orderedPrice,                     // السعر المستخدم من التوريد
                    'notes' => sprintf(
                        'Stock deducted for Order #%s from %s #%s with price %s',
                        $order->order_id,
                        \Illuminate\Support\Str::headline(class_basename($supply->transactionable_type ?? 'Unknown')),
                        $supply->transactionable_id ?? 'N/A',
                        number_format($orderedPrice, 2)
                    ),
                    'movement_type'         => 'out',                              // نوع الحركة: إخراج من المخزون
                    'created_at'      => $order->created_at,                              // تاريخ تنفيذ الحركة
                    'transactionable_id'    => $supply->transactionable_id,       // معرف مصدر التوريد (مثل PurchaseInvoice)
                    'transactionable_type' => $transactionableType,

                    'source_transaction_id' => $supply->id,                        // السطر الأصلي الذي خرجت منه الكمية (FIFO)
                ];

                // تحديث الكمية المتبقية في التوريد المستخدم
                $supplies[$key]->quantity -= $allocatedQty / $supply->package_size;

                // تقليل الكمية المطلوبة من الطلب
                $remainingQty -= $allocatedQty;

                // إذا اكتفينا من هذا الطلب، ننتقل للطلب التالي
                if ($remainingQty <= 0) break;
            }
        }
        // self::saveAllocations($allocations);
        return $allocations; // إرجاع نتائج التخصيص الجاهزة للحفظ

    }


    public function allocateForOrders(): array
    {
        $allocations = [];

        // 1. جلب الطلبات الجاهزة أو المسلمة
        $orders = Order::with(['orderDetails.product', 'orderDetails.unit'])
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereNull('deleted_at')
            ->orderBy('id') // FIFO logic
            ->get();

        // 2. المرور على كل طلب وتفاصيله
        foreach ($orders as $order) {
            foreach ($order->orderDetails as $detail) {
                $productId = $detail->product_id;
                $unitId = $detail->unit_id;

                $unitPrice = UnitPrice::where('product_id', $productId)
                    ->where('unit_id', $unitId)
                    ->first();
                $allocations[] = [
                    $unitPrice
                ];
            }
        }

        return $allocations;
    }
}
