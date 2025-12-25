<?php

namespace App\Imports;

use Throwable;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;

class OrdersImport implements ToCollection, WithHeadingRow, SkipsOnFailure
{
    use SkipsFailures;

    private int $successCount = 0;
    private ?Order $currentOrder = null;

    public function collection(Collection  $rows)
    {
        DB::beginTransaction();
        if ($rows->isEmpty()) {
            return;
        }

        try {
            foreach ($rows as $row) {
                $row = $row->toArray();

                $rowType = strtolower(trim($row['row_type'] ?? ''));
                if ($rowType === 'header') {
                    $this->currentOrder = Order::create([
                        'id' => $row['order_id'],
                        'branch_id' => $row['branch_id'],
                        'customer_id' => $row['customer_id'],
                        'status' => $row['status'] ?? Order::ORDERED,
                        'notes' => $row['notes'] ?? '',
                        'created_at' => $row['created_at'] ?? now(),
                        'is_purchased' => 0,
                    ]);
                }

                if ($rowType === 'detail' && $this->currentOrder) {
                    $detail = OrderDetails::create([
                        'order_id' => $this->currentOrder->id,
                        'product_id' => $row['product_id'],
                        'unit_id' => $row['unit_id'],
                        'quantity' => $row['quantity'],
                        'price' => $row['price'],
                        'available_quantity' => $row['available_quantity'] ?? $row['quantity'],
                        'package_size' => getUnitPricePackageSize($row['product_id'], $row['unit_id']) ?? 1,
                    ]);
                    if (in_array($row['status'], [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])) {
                        $this->createInventoryTransaction($detail);
                    }
                }
            }

            DB::commit();
            $this->successCount++;
        } catch (Throwable $e) {
            DB::rollBack();
        }
    }


    public function headingRow(): int
    {
        return 1;
    }

    public function getSuccessfulImportsCount(): int
    {
        return $this->successCount;
    }

    private function createInventoryTransaction(OrderDetails $detail): void
    {
        $order = $detail->order;

        $product = $detail->product;

        if ($product) {
            $storeId = 1;
            if ($product->is_manufacturing) {
                $storeId = 8;
            }
            InventoryTransaction::create([
                'product_id'           => $detail->product_id,
                'movement_type'        => InventoryTransaction::MOVEMENT_OUT,
                'quantity'             => $detail->available_quantity,
                'unit_id'              => $detail->unit_id,
                'package_size'         => $detail->package_size, // إذا عندك من ملف الإكسل أو بيانات إضافية عدّلها
                'price'                => $detail->price,
                'movement_date'        => $order->order_date ?? now(),
                'transaction_date'     => $order->order_date ?? now(),
                'store_id'             => $storeId, // لو عندك store_id عدّله بناءً على الإكسل أو الطلب
                'notes'                => "Stock deducted for Order #{$order->id} (imported)",
                'transactionable_id'   => $order->id,
                'transactionable_type' => Order::class,
            ]);
        }
    }
}
