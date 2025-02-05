<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoiceDetail;
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

    private function getRemainingQty()
    {
        $queryIn = DB::table('inventory_transactions')
            ->where('product_id', $this->productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_PURCHASE_INVOICE)
            ->select('reference_id', DB::raw('SUM(quantity * package_size) as remaining_qty'));

        if (!is_null($this->storeId)) {
            $queryIn->where('store_id', $this->storeId);
        }

        $queryIn->groupBy('reference_id');
        $purchaseData = $queryIn->get();

        $result = [];
        foreach ($purchaseData as $data) {
            $result[] = [
                'reference_id' => $data->reference_id,
                'remaining_qty' => $data->remaining_qty,
            ];
        }

        return $result;
    }
}
