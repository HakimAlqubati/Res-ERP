<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Get the inventory report based on filters for product and unit.
     *
     * @param int|null $productId
     * @param int|null $unitId
     * @return array
     */
    public $productId;
    public $unitId;

    public function __construct($productId, $unitId)
    {
        $this->productId = $productId;
        $this->unitId = $unitId;
    }
    public function getInventoryReport()
    {
        return  $this->getRemainingQty();
    }

    private function getRemainingQty()
    { 
        $totalIn = DB::table('inventory_transactions')
            ->where('product_id', $this->productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_PURCHASE_INVOICE)
            ->sum(DB::raw('quantity * package_size'));

        $totalOut = DB::table('inventory_transactions')
            ->where('product_id', $this->productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_ORDERS)
            ->sum(DB::raw('quantity * package_size'));
        $remQty = $totalIn - $totalOut;
        $unitPrices = $this->getProductUnitPrices();
        $result = [];
        foreach ($unitPrices as  $unitPrice) {
            $result[] = [
                'unit_id' => $unitPrice['unit_id'],
                'order' => $unitPrice['order'],
                'package_size' => $unitPrice['package_size'],
                'unit_name' => $unitPrice['unit_name'],
                'remaining_qty' => round($remQty / $unitPrice['package_size'], 2),
            ];
        }
        return $result;
        return;
    }
    private function getProductUnitPrices()
    { 
        // Fetch the product with its unitPrices and related unit information
        $query = Product::find($this->productId)
            ->unitPrices()->orderBy('order', 'asc')
            ->with('unit'); // Assuming 'unitPrices' has a relationship to 'Unit'

        // Apply unit_id filter
        if ($this->unitId !== 'all') {
            if (is_array($this->unitId)) {
                // If unit_id is an array, use 'whereIn'
                $query->whereIn('unit_id', $this->unitId);
            } else {
                // If unit_id is a single value, use 'where'
                $query->where('unit_id', $this->unitId);
            }
        }

        // Get the results and map them to include unit_name
        $productUnitPrices = $query->get(['unit_id', 'order', 'package_size']);

        $result = $productUnitPrices->map(function ($unitPrice) {
            return [
                'unit_id' => $unitPrice->unit_id,
                'order' => $unitPrice->order,
                'package_size' => $unitPrice->package_size,
                'unit_name' => $unitPrice->unit->name, // Assuming the unit name is stored in the 'name' column
            ];
        });

        return $result;
    }
}
