<?php

namespace App\Services\FixFifo;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\ProductPriceHistory;
use App\Models\Unit;
use App\Models\UnitPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FifoAllocatorService
{
    public function allocate(int $productId): array
    {


        $product = \App\Models\Product::find($productId);

        if (! $product) {
            // خيار 1: تجاهل المنتج
            // return [];

            // خيار 2: لو تحب تسجل رسالة خطأ:
            Log::warning("⛔ Product not found for product_id: {$productId}");
            return [];
        }

        $storeId = defaultManufacturingStore($product)->id;
        // Step 1: Get supply (movement_type = 'in') ordered by transaction_date
        $supplies = DB::table('inventory_transactions')
            ->where('movement_type', 'in')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
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


        $adjustmentDetails = DB::table('stock_adjustment_details as sad')
            ->where('sad.product_id', $productId)
            ->where('sad.adjustment_type', 'decrease')
            ->whereNull('sad.deleted_at')
            ->orderBy('sad.id')
            ->get([
                'sad.unit_id',
                'sad.package_size',
                'sad.quantity as available_quantity',
                'sad.id as order_id',
                'sad.adjustment_date as created_at',
                DB::raw("'adjustment_decrease' as source_type")
            ]);

        $allocations = [];
        $allOrders = $orders->merge($issueOrders);
        $allOrders = $allOrders->sortBy('created_at')->values();
        $allOrders = $orders
            ->merge($issueOrders)
            ->merge($adjustmentDetails)
            ->sortBy('created_at')
            ->values();

        foreach ($allOrders as $order) {
            // $transactionableType = isset($order->stock_issue_order_id) ? \App\Models\StockIssueOrder::class : \App\Models\Order::class;
            $transactionableType = match ($order->source_type ?? null) {
                'stock_issue' => \App\Models\StockIssueOrder::class,
                'adjustment_decrease' => \App\Models\StockAdjustmentDetail::class,
                default => \App\Models\Order::class,
            };
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
                $transactionNotes = sprintf(
                    'Stock deducted for Order #%s from %s #%s with price %s',
                    $order->order_id,
                    \Illuminate\Support\Str::headline(class_basename($supply->transactionable_type ?? 'Unknown')),
                    $supply->transactionable_id ?? 'N/A',
                    number_format($orderedPrice, 2)
                );
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
                    'notes' => $transactionNotes,
                    'movement_type'         => 'out',                              // نوع الحركة: إخراج من المخزون
                    'created_at'      => $order->created_at,                              // تاريخ تنفيذ الحركة
                    'transactionable_id'    => $supply->transactionable_id,       // معرف مصدر التوريد (مثل PurchaseInvoice)
                    'transactionable_type' => $transactionableType,

                    'source_transaction_id' => $supply->id,                        // السطر الأصلي الذي خرجت منه الكمية (FIFO)
                ];


                $sourceTransaction = \App\Models\InventoryTransaction::find($supply->id);

                if (! $sourceTransaction) {
                    throw new \Exception("⛔ InventoryTransaction not found for supply ID: {$supply->id}");
                }

                $notes = sprintf(
                    'Updated by FIFO from %s #%s',
                    \Illuminate\Support\Str::headline(class_basename($sourceTransaction->transactionable_type)),
                    $sourceTransaction->transactionable_id
                );


                $this->updateUnitPricesFromSupply(
                    $productId,
                    $supply->price,
                    $supply->package_size,
                    $supply->transaction_date,
                    $notes
                );

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


    public function updateUnitPricesFromSupply(
        int $productId,
        float $supplyPrice,
        float $supplyPackageSize,
        $date,
        $notes


    ) {
        $unitPrices = UnitPrice::where('product_id', $productId)
            ->get()
            ->keyBy('unit_id');


        foreach ($unitPrices as $unitId => $unitPrice) {

            $newPrice = ($supplyPrice / $supplyPackageSize) * $unitPrice->package_size;
            // Log::info('newprice', [$newPrice]);
            // Log::info('unitid', [$unitId]);
            $unitPrice->update([
                'price' => $newPrice,
                'date' => $date,
                'notes' => $notes
            ]);
            // $res[] = [ 
            //     'newprice' => $newPrice,
            //     'date' => $date,
            //     'notes' => $notes,
            //     'unit' => $unitPrice->unit->name,
            //     'supplypackageSize' => $supplyPackageSize,

            // ];
        }
        // return $res;
    }
}
