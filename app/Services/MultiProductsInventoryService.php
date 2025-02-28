<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class MultiProductsInventoryService
{
    public $productId;
    public $unitId;
    public $storeId;
    public $categoryId;

    public function __construct($categoryId = null, $productId = null, $unitId = 'all', $storeId = null)
    {
        $this->categoryId = $categoryId;
        $this->productId = $productId;
        $this->unitId = $unitId;
        $this->storeId = $storeId;
    }

    public function getInventoryReport()
    {
        if ($this->productId) {
            return [$this->getInventoryForProduct($this->productId)];
        }

        // Fetch all products or filter by category if provided
        $query = Product::query();

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        // Use pagination (5 products per page)
        // $products = $query->paginate(15);
        $products = $query->get();

        $report = [];
        foreach ($products as $product) {
            $report[] = $this->getInventoryForProduct($product->id);
        }
        return [
            'reportData' => $report,
            'pagination' => $products, // Pass pagination data
        ];
    }


    private function getInventoryForProduct($productId)
    {
        $queryIn = DB::table('inventory_transactions')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN);

        $queryOut = DB::table('inventory_transactions')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT);

        if (!is_null($this->storeId)) {
            $queryIn->where('store_id', $this->storeId);
            $queryOut->where('store_id', $this->storeId);
        }

        $totalIn = $queryIn->sum(DB::raw('quantity * package_size'));
        $totalOut = $queryOut->sum(DB::raw('quantity * package_size'));

        $remQty = $totalIn - $totalOut;
        $unitPrices = $this->getProductUnitPrices($productId);
        $product = Product::find($productId);

        $result = [];
        foreach ($unitPrices as $unitPrice) {
            $result[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'unit_id' => $unitPrice['unit_id'],
                'order' => $unitPrice['order'],
                'package_size' => $unitPrice['package_size'],
                'unit_name' => $unitPrice['unit_name'],
                'remaining_qty' => round($remQty / $unitPrice['package_size'], 2),
                'minimum_quantity' => $unitPrice['minimum_quantity'],
                'is_last_unit' => $unitPrice['is_last_unit']
            ];
        }
        return $result;
    }

    public function getProductUnitPrices($productId)
    {
        $query = Product::find($productId)
            ->unitPrices()->orderBy('order', 'asc')
            ->with('unit');

        if ($this->unitId !== 'all') {
            if (is_array($this->unitId)) {
                $query->whereIn('unit_id', $this->unitId);
            } else {
                $query->where('unit_id', $this->unitId);
            }
        }

        $productUnitPrices = $query->get(['unit_id', 'order', 'package_size', 'minimum_quantity']);
        // Find the highest order value to determine the last unit
        $maxOrder = $productUnitPrices->max('order');

        return $productUnitPrices->map(function ($unitPrice) use ($maxOrder) {
            return [
                'unit_id' => $unitPrice->unit_id,
                'order' => $unitPrice->order,
                'package_size' => $unitPrice->package_size,
                'unit_name' => $unitPrice->unit->name,
                'minimum_quantity' => $unitPrice->minimum_quantity ?? 0,
                'is_last_unit' => $unitPrice->order == $maxOrder, // True if this is the last unit
            ];
        });
    }


    public function getProductsBelowMinimumQuantity()
    {
        $inventory = $this->getInventoryReport();
        $lowStockProducts = [];
        
        foreach ($inventory['reportData'] as $productData) {
            
            foreach ($productData as $product) {
                if ($product['is_last_unit'] == true && $product['remaining_qty'] <= $product['minimum_quantity']) {
                    $lowStockProducts[] = $product;
                }
            }
        }
        
        // dd($inventory,$lowStockProducts);
        return $lowStockProducts;
    }
}
