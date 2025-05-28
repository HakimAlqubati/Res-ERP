<?php

namespace App\Services\Reports;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockSupplyOrder;
use App\Services\MultiProductsInventoryService;
use Illuminate\Support\Facades\DB;

class InventoryWithUsageReportService
{
    protected MultiProductsInventoryService $inventoryService;

    public function __construct(
        public int $storeId,
        public ?int $categoryId = null,
        public ?int $productId = null,
    ) {
        $this->inventoryService = new MultiProductsInventoryService(
            $this->categoryId,
            $this->productId,
            'all',
            $this->storeId,
            true,
        );
    }

    public function getReport(): array
    {
        $categoryId = $this->categoryId;
        $productId = $this->productId;
        $unitId = 'all';
        $storeId = $this->storeId;

        $inventoryService = new MultiProductsInventoryService($categoryId, $productId, $unitId, $storeId, 1);

        $products = Product::whereHas('inventoryTransactions', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->whereHas('usedInProducts')
            ->when($productId, fn($q) => $q->where('id', $productId))
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->get();

        $finalTotalPrice = 0;
        $finalPrice = 0;
        $totalUsedQty = 0;
        $totalOrderedQty = 0;

        $reportData = collect();

        foreach ($products as $product) {
            $productUnits = $inventoryService->getInventoryForProduct($product->id);

            if (empty($productUnits)) {
                continue;
            }

            $smallestUnit = collect($productUnits)->sortBy('package_size')->first();

            if (!$smallestUnit) {
                continue;
            }
            $productUnits = [$smallestUnit];
            $usedQty = $this->getUsedQuantity($product->id);
            $orderedQty = $this->getOrderedQuantity($product->id);
            $totalUsedQty += $usedQty;
            $totalOrderedQty += $orderedQty;

            $formattedUnits = collect($productUnits)->map(function ($unitData) use (
                $usedQty,
                $orderedQty,
                &$finalTotalPrice,
                &$finalPrice
            ) {
                $packageSize = $unitData['package_size'];
                $price = getUnitPrice($unitData['product_id'], $unitData['unit_id']);
                $usedQtyPerUnit = $usedQty * $packageSize;
                $remQty = $unitData['remaining_qty'];
                $totalPrice = $price * $remQty;

                $finalTotalPrice += $totalPrice;
                $finalPrice += $price;
                $orderedQtyPerUnit = $orderedQty / $packageSize;

                return array_merge($unitData, [
                    'used_quantity'     => formatQunantity($usedQtyPerUnit),
                    'ordered_quantity'  => formatQunantity($orderedQtyPerUnit),
                    'remaining_qty'     => formatQunantity($remQty),
                    'price'             => formatMoneyWithCurrency($price),
                    'total_price'       => formatMoneyWithCurrency($totalPrice),
                ]);
            });

            $reportData->push($formattedUnits);
        }

        return [
            'reportData' => $reportData,
            'pagination' => null, // لأننا استخدمنا get() بدون pagination
            'final_price' => formatMoneyWithCurrency($finalPrice),
            'final_total_price' => formatMoneyWithCurrency($finalTotalPrice),
            'total_used_quantity' => formatQunantity($totalUsedQty),
            'total_ordered_quantity' => formatQunantity($totalOrderedQty),
        ];
    }



    protected function getUsedQuantity(int $productId): float
    {
        $usedQty = DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('store_id', $this->storeId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->where('transactionable_type', StockSupplyOrder::class)
            ->sum(DB::raw('quantity * package_size'));
        return round($usedQty, 2);
    }

    protected function getOrderedQuantity(int $productId): float
    {
        return DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('store_id', $this->storeId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where('transactionable_type', Order::class)
            ->sum(DB::raw('quantity * package_size'));
    }
}
