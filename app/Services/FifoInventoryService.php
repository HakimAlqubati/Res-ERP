<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class FifoInventoryService
{
    private $productId;
    private $orderQuantity;
    private $unitId;

    public function __construct($productId, $orderQuantity, $unitId)
    {
        $this->productId = $productId;
        $this->orderQuantity = $orderQuantity;
        $this->unitId = $unitId;
    }

    /**
     * Allocate order quantity using FIFO logic and return the order details array.
     *
     * @return array
     * @throws \Exception
     */
    public function allocateFIFOOrder()
    {
        $availableQuantities = $this->getAvailableQuantities();
        dd($availableQuantities);
        
    }

    /**
     * Get available quantities for the product, sorted by oldest purchase first.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getAvailableQuantities()
    {
        return DB::table('inventory_transactions')
            ->where('product_id', $this->productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_PURCHASE_INVOICE)
            ->orderBy('movement_date', 'asc')
            ->get(['id', 'quantity', 'package_size', 'price', 'movement_date','reference_id'])
            ->groupBy('reference_id')
            ;
    }
}
