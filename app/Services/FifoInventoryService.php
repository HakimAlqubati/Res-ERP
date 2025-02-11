<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use Exception;
use App\Services\InventoryService;

class FifoInventoryService
{
    private $productId;
    private $orderQuantity;
    private $unitId;
    private $inventoryService;

    public function __construct($productId, $unitId, $orderQuantity)
    {
        $this->productId = $productId;
        $this->unitId = $unitId;
        $this->orderQuantity = $orderQuantity;

        // إنشاء كائن من InventoryService لجلب تفاصيل الوحدات
        $this->inventoryService = new InventoryService($productId, $unitId);
    }

    /**
     * Allocate order quantity using FIFO logic and return the allocation details.
     *
     * @return array
     * @throws \Exception
     */
    public function allocateFIFOOrder(): array
    {
        $availablePurchases = $this->getPurchaseQuantities();
        $remainingQuantity = $this->orderQuantity;
        $allocations = [];

        // جلب تفاصيل الوحدات المرتبطة بالمنتج
        $unitPrices = $this->inventoryService->getProductUnitPrices($this->productId);
        $packageSize = $this->getPackageSizeForUnit($unitPrices, $this->unitId);

        foreach ($availablePurchases as $purchase) {
            if ($remainingQuantity <= 0) {
                break;
            }

            // Step 1: Calculate already ordered quantities from this purchase
            $previousOrders = $this->getOrderedQuantities($purchase->reference_id);
            $totalOrderedQty = $previousOrders->sum(fn($order) => $order->quantity * $order->package_size);

            // Step 2: Determine remaining quantity available for allocation
            $availableQty = ($purchase->quantity * $purchase->package_size) - $totalOrderedQty;
            $adjustedPrice = ($purchase->price / $purchase->package_size) * $packageSize;
            // تحويل الكمية المتاحة إلى الوحدة المطلوبة باستخدام package_size
            $availableQtyInUnit = $availableQty / $packageSize;

            if ($availableQtyInUnit <= 0) {
                continue; // Skip if no quantity is left from this purchase
            }

            // Step 3: Determine the quantity to allocate
            $allocatedQty = min($availableQtyInUnit, $remainingQuantity);


            // Step 4: Record allocation details
            $allocations[] = [
                'purchase_invoice_id' => $purchase->reference_id,
                'allocated_qty' => $allocatedQty,
                'unit_id' => $this->unitId,
                'product_id' => $this->productId,
                'unit_price' => round($adjustedPrice, 2),
                'package_size' => $packageSize,
                'movement_date' => $purchase->movement_date,
            ];

            // Step 5: Reduce the remaining quantity
            $remainingQuantity -= $allocatedQty;
        }

        // If there is remaining quantity that couldn't be allocated, throw an exception
        if ($remainingQuantity > 0) {
            throw new Exception("Insufficient inventory. {$remainingQuantity} units could not be allocated.");
        }

        return $allocations;
    }

    /**
     * Get the package size for the specified unit.
     *
     * @param array $unitPrices
     * @param int $unitId
     * @return float
     */
    private function getPackageSizeForUnit($unitPrices, $unitId)
    {
        foreach ($unitPrices as $unitPrice) {
            if ($unitPrice['unit_id'] == $unitId) {
                return $unitPrice['package_size'];
            }
        }
        throw new Exception("Unit ID {$unitId} not found for product {$this->productId}.");
    }

    /**
     * Get available quantities for the product, sorted by oldest purchase first.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getPurchaseQuantities()
    {
        return InventoryTransaction::query()
            ->where('product_id', $this->productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_PURCHASE_INVOICE)
            ->orderBy('movement_date', 'asc')
            ->get(['id', 'quantity', 'package_size', 'price', 'movement_date', 'reference_id', 'store_id']);
    }

    /**
     * Get the total quantity already ordered from a specific purchase invoice.
     *
     * @param int $purchaseId
     * @return \Illuminate\Support\Collection
     */
    private function getOrderedQuantities($purchaseId)
    {
        return InventoryTransaction::query()
            ->where('product_id', $this->productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_ORDERS)
            ->where('purchase_invoice_id', $purchaseId)
            ->get(['id', 'quantity', 'unit_id', 'package_size']);
    }


    /**
     * @return array
     */
    public function allocateOrder(): array
    {
        try {
            $this->inventoryService = new InventoryService($this->productId, $this->unitId);
            return [
                'success' => true,
                'result' => $this->allocateFIFOOrder(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
