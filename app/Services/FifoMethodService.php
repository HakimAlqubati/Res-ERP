<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Unit;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class FifoMethodService
{
    public $productId;
    public $unitId;
    public $storeId;
    public $requestedQuantity;
    private $requestedQuantityLargerThanAvailable;

    public function __construct($productId, $unitId, $requestedQuantity, $storeId = null)
    {
        $this->productId = $productId;
        $this->unitId = $unitId;
        $this->requestedQuantity = $requestedQuantity;
        $this->storeId = $storeId;

        $this->requestedQuantityLargerThanAvailable = setting('completed_order_if_not_qty');
    }

    /**
     * Calculate the remaining quantity using FIFO method.
     *
     * @param int $requestedQuantity
     * @return array
     */
    public function calculateRemainingQuantity($requestedQuantity)
    {
        $inventoryReport = $this->getRemainingQty();
        dd($inventoryReport);
        // Check if the requested quantity is larger than available
        $totalAvailableQty = array_sum(array_column($inventoryReport, 'remaining_qty'));
        if ($requestedQuantity > $totalAvailableQty) {
            if (!$this->requestedQuantityLargerThanAvailable) {
                return [
                    'status' => 'error',
                    'message' => 'Quantity exceeds available stock',
                    'allocated' => [],
                    'unfulfilled_quantity' => $requestedQuantity,
                    'total_remaining_quantity' => $totalAvailableQty,

                ];
            }
            $this->requestedQuantityLargerThanAvailable = true;
        }

        // Pre-fetch purchase details for all reference_ids to avoid multiple queries
        $purchaseDetails = PurchaseInvoiceDetail::whereIn('purchase_invoice_id', array_column($inventoryReport, 'reference_id'))
            ->where('product_id', $this->productId)
            ->get()
            ->keyBy('purchase_invoice_id');

        $allocatedTransactions = [];
        $remainingToAllocate = $requestedQuantity;

        foreach ($inventoryReport as $unitReport) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $availableQty = $unitReport['remaining_qty'];
            $referenceId = $unitReport['reference_id'];

            if ($availableQty > 0 && isset($purchaseDetails[$referenceId])) {
                $purchaseDetail = $purchaseDetails[$referenceId];

                $allocatedQty = min($availableQty, $remainingToAllocate);
                $allocatedTransactions[] = [
                    'reference_id' => $referenceId,
                    'purchase_invoice_detail' => [
                        'price' => $purchaseDetail->price,
                        'package_size' => $purchaseDetail->package_size,
                        'unit_id' => $purchaseDetail->unit_id,
                    ],
                    'remaining_quantity' => $availableQty,
                    'unit_id' => $this->unitId,
                    'unit' => Unit::find($this->unitId)?->name ?? '',
                    'allowed_quantity' => $allocatedQty,
                ];

                $remainingToAllocate -= $allocatedQty;
            }
        }

        return [
            'status' => 'success',
            'allocated' => $allocatedTransactions,
            'unfulfilled_quantity' => $remainingToAllocate,
        ];
    }

    private function getRemainingQty_new()
    {
        $query = DB::table('inventory_transactions')
            ->where('product_id', $this->productId)
            ->whereIn('movement_type', [
                InventoryTransaction::MOVEMENT_PURCHASE_INVOICE,
                InventoryTransaction::MOVEMENT_ORDERS,
            ])
            ->select(
                'reference_id',
                'unit_id',
                'package_size',
                DB::raw("SUM(
                    CASE 
                        WHEN movement_type = '" . InventoryTransaction::MOVEMENT_PURCHASE_INVOICE . "' THEN quantity * package_size 
                        WHEN movement_type = '" . InventoryTransaction::MOVEMENT_ORDERS . "' THEN quantity * package_size 
                        ELSE 0 
                    END
                ) as quantity")
            );

        if (!is_null($this->storeId)) {
            $query->where('store_id', $this->storeId);
        }

        $data = $query->groupBy('reference_id', 'unit_id', 'package_size', 'movement_type')
            ->get();
        dd($data);
    }
    private function getRemainingQty()
    {
        $inventoryService = new InventoryService($this->productId, $this->unitId);
        $queryIn = DB::table('inventory_transactions')
            ->where('product_id', $this->productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_PURCHASE_INVOICE)
            ->select('reference_id', 'unit_id', 'package_size', DB::raw('SUM(quantity * package_size) as quantity'));

        $queryOut = DB::table('inventory_transactions')
            ->where('product_id', $this->productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_ORDERS)
            ->select('reference_id', 'unit_id', 'package_size', DB::raw('SUM(quantity * package_size) as quantity'));


        if (!is_null($this->storeId)) {
            $queryIn->where('store_id', $this->storeId);
            $queryOut->where('store_id', $this->storeId);
        }

        $queryIn->groupBy('reference_id', 'unit_id', 'package_size');
        $queryOut->groupBy('reference_id', 'unit_id', 'package_size');
        $purchaseData = $queryIn->get();
        $orderData = $queryOut->get();
        dd($purchaseData, $orderData);
        $unitPrices = $inventoryService->getProductUnitPrices();
        $result = [];
        foreach ($purchaseData as $data) {
            $result[] = [
                'reference_id' => $data->reference_id,
                'remaining_qty' => $data->quantity,
            ];
        }

        return $result;
    }
}
