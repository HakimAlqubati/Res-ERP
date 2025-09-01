<?php

namespace App\Services\Orders;

use Exception;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Services\MultiProductsInventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderInventoryAllocator
{
    protected Order $order;
    protected array $storesUsed = [];

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function allocateFromManagedStores(array $storeIds): void
    {
        foreach ($this->order->orderDetails as $orderDetail) {
            $requiredQty = $orderDetail->available_quantity;
            $productId = $orderDetail->product_id;
            $unitId = $orderDetail->unit_id;

            $totalAvailable = 0;
            $storeReports = [];

            // $storeIds = [1, 5];
            // Step 1: Aggregate availability across all stores
            foreach ($storeIds as $storeId) {
                $inventoryService = new MultiProductsInventoryService(null, $productId, $unitId, $storeId);
                $report = $inventoryService->getInventoryReportWithPagination(15)['reportData'];
                $availableQty = $report[0][0]['remaining_qty'] ?? 0;

                if ($availableQty > 0) {
                    $storeReports[] = [
                        'store_id' => $storeId,
                        'available_qty' => $availableQty,
                    ];
                    $totalAvailable += $availableQty;
                }
            }

            // Step 2: Validation
            if ($totalAvailable < $requiredQty) {
                Log::error("\u274C Insufficient total stock across stores for product", [$orderDetail->product?->name, $orderDetail->unit?->name]);
                Log::info('total_available', [$totalAvailable]);
                Log::info('required_qty', [$requiredQty]);
                throw new Exception("\u274C Insufficient total stock across stores for product {$orderDetail->product?->name}");
            }

            // Step 3: Deduct from stores
            foreach ($storeReports as $report) {
                if ($requiredQty <= 0) break;

                $deductQty = min($report['available_qty'], $requiredQty);

                InventoryTransaction::create([
                    'product_id'           => $productId,
                    'movement_type'        => InventoryTransaction::MOVEMENT_OUT,
                    'quantity'             => $deductQty,
                    'unit_id'              => $unitId,
                    'purchase_invoice_id'  => $orderDetail->purchase_invoice_id,
                    'movement_date'        => $this->order->order_date ?? now(),
                    'package_size'         => $orderDetail->package_size,
                    'store_id'             => $report['store_id'],
                    'transaction_date'     => $this->order->order_date ?? now(),
                    'notes'                => 'Inventory created for order ' . $this->order->id,
                    'transactionable_id'   => $this->order->id,
                    'transactionable_type' => Order::class,
                ]);

                $this->storesUsed[] = $report['store_id'];
                $requiredQty -= $deductQty;
            }
        }

        $this->order->stores()->syncWithoutDetaching(array_unique($this->storesUsed));
    }
}
