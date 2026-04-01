<?php

namespace App\Services;

use App\Models\UnitPrice;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MultiProductsInventoryServiceV2
{
    // Encapsulated — state must not be mutated from outside the class
    protected $productId;
    protected $unitId;
    protected $storeId;
    protected $categoryId;
    protected $filterOnlyAvailable;
    protected $productIds = [];
    protected $isActive = false;

    public function __construct(
        $categoryId = null,
        $productId = null,
        $unitId = 'all',
        $storeId,
        $filterOnlyAvailable = false
    ) {
        $this->categoryId          = $categoryId;
        $this->productId           = $productId;
        $this->unitId              = $unitId;
        $this->storeId             = $storeId;
        $this->filterOnlyAvailable = $filterOnlyAvailable;
    }

    public function getInventoryReport(): array
    {
        // Priority 1: explicit product IDs list
        if (!empty($this->productIds)) {
            $reportArr = [];
            foreach ($this->productIds as $productId) {
                $reportArr[] = $this->getInventoryForProduct($productId);
            }

            return [
                'reportData' => $reportArr,
                'pagination' => null,
            ];
        }

        // Priority 2: single product
        if ($this->productId) {
            return [
                'reportData' => [$this->getInventoryForProduct($this->productId)],
                'pagination' => null,
            ];
        }

        // Priority 3: all products (optionally filtered by category / active)
        $query = Product::query();

        if ($this->isActive) {
            $query->where('active', true);
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        $products  = $query->get();
        $reportArr = [];

        foreach ($products as $product) {
            $reportArr[] = $this->getInventoryForProduct($product->id);
        }

        return [
            'reportData' => $reportArr,
            'pagination' => $products,
        ];
    }


    public function getInventoryReportWithPagination($perPage = 15): array
    {
        $query = Product::query();

        if ($this->isActive) {
            $query->where('active', true);
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        // Priority 1: explicit product IDs list — no DB pagination needed
        if (!empty($this->productIds)) {
            $reportArr = [];
            foreach ($this->productIds as $productId) {
                $reportArr[] = $this->getInventoryForProduct($productId);
            }

            return [
                'reportData' => $reportArr,
                'pagination' => null,
                'totalPages' => 1,
            ];
        }

        // Priority 2: single product
        if ($this->productId) {
            return [
                'reportData' => [$this->getInventoryForProduct($this->productId)],
                'pagination' => null,
                'totalPages' => 1,
            ];
        }

        // Priority 3: filter available-only — pagination must be applied manually
        // because availability is determined after inventory calculation, not at DB level
        if ($this->filterOnlyAvailable) {
            $allProducts    = $query->get();
            $filteredReport = [];

            foreach ($allProducts as $product) {
                $productInventory = $this->getInventoryForProduct($product->id);
                $totalRemaining   = collect($productInventory)->sum('remaining_qty');

                if ($totalRemaining > 0) {
                    $filteredReport[] = $productInventory;
                }
            }

            $currentPage = request()->get('page', 1);
            $collection  = collect($filteredReport);
            $pagedData   = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $pagination = new LengthAwarePaginator($pagedData, $collection->count(), $perPage, $currentPage, [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]);

            return [
                'reportData' => $pagedData,
                'pagination' => $pagination,
                'totalPages' => $pagination->lastPage(),
            ];
        }

        // Priority 4: standard DB-level pagination
        $products = $query->paginate($perPage);
        $report   = [];

        foreach ($products as $product) {
            $report[] = $this->getInventoryForProduct($product->id);
        }

        return [
            'reportData' => $report,
            'pagination' => $products,
            'totalPages' => $products->lastPage(),
        ];
    }


    public function getInventoryForProduct($productId, $productIds = []): array
    {
        // Allows batch call via second param (legacy support)
        if (!empty($productIds)) {
            $result = [];
            foreach ($productIds as $id) {
                $result = array_merge($result, $this->getInventoryForProduct($id));
            }
            return $result;
        }

        // --- Price resolution (no store filter — prices are global across stores) ---
        $transactionPrices = InventoryTransaction::query()
            ->select('unit_id', DB::raw('MAX(id) as last_id'))
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->groupBy('unit_id')
            ->pluck('last_id', 'unit_id');

        $unitTransactions = [];

        if ($transactionPrices->count()) {
            InventoryTransaction::query()
                ->whereIn('id', $transactionPrices->values())
                ->get(['unit_id', 'price', 'store_id'])
                ->each(function ($row) use (&$unitTransactions) {
                    $unitTransactions[$row->unit_id] = [
                        'price'    => $row->price,
                        'store_id' => $row->store_id,
                    ];
                });
        }

        // --- Quantity queries (scoped to store) ---
        $queryIn = InventoryTransaction::query()
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where('store_id', $this->storeId);

        $queryOut = InventoryTransaction::query()
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->where('store_id', $this->storeId);

        $totalIn  = $queryIn->sum(DB::raw('quantity * package_size'));
        $totalOut = $queryOut->sum(DB::raw('quantity * package_size'));

        $totalBaseIn  = $queryIn->sum(DB::raw('IFNULL(base_quantity, quantity * package_size)'));
        $totalBaseOut = $queryOut->sum(DB::raw('IFNULL(base_quantity, quantity * package_size)'));

        // Compute once — never mutated inside the loop below
        $rawRemainingQty  = $totalIn - $totalOut;
        $baseRemainingQty = round($totalBaseIn - $totalBaseOut, 4);

        $unitPrices = $this->getProductUnitPrices($productId);
        $product    = Product::find($productId);
        $result     = [];

        if (!$product) {
            return $result;
        }

        $baseUnitPrice = $product->supplyOutUnitPrices()
            ->orderBy('package_size', 'asc')
            ->first();

        foreach ($unitPrices as $unitPrice) {
            if ($unitPrice['package_size'] <= 0) continue;

            $packageSize = $unitPrice['package_size'];

            // Each unit's remaining is always derived from the same base — no cross-iteration mutation
            $remainingQty         = round($rawRemainingQty / $packageSize, 4);
            $remainingBaseQtyUnit = round($baseRemainingQty / $packageSize, 4);

            $unitId          = $unitPrice['unit_id'];
            $priceFromInventory = $unitTransactions[$unitId]['price'] ?? null;
            $priceStoreId    = $unitTransactions[$unitId]['store_id'] ?? null;
            $priceSource     = 'inventory';

            // Fallback 1: calculate price proportionally from base unit
            if (is_null($priceFromInventory)) {
                $firstUnitPrice = $unitPrices->firstWhere('order', 1);
                if ($firstUnitPrice && isset($unitTransactions[$firstUnitPrice['unit_id']])) {
                    $basePackageSize    = max($firstUnitPrice['package_size'] ?? 1, 1);
                    $basePrice          = $unitTransactions[$firstUnitPrice['unit_id']]['price'];
                    $priceStoreId       = $unitTransactions[$firstUnitPrice['unit_id']]['store_id'];
                    $priceFromInventory = round(($packageSize / $basePackageSize) * $basePrice, 2);
                    $priceSource        = 'inventory (calculated)';
                }
            }

            // Fallback 2: use unit_prices table
            if (is_null($priceFromInventory)) {
                $priceFromInventory = $unitPrice['price'] ?? null;
                $priceSource        = 'unit_price';
                $priceStoreId       = null;
            }

            // Store #1 is the main/central store — negative quantities are masked intentionally.
            // TODO: move magic value to config('inventory.main_store_id')
            $result[] = [
                'product_id'             => $productId,
                'product_active'         => $product->active,
                'product_code'           => $product->code,
                'product_name'           => $product->name,
                'unit_id'                => $unitPrice['unit_id'],
                'order'                  => $unitPrice['order'],
                'package_size'           => $unitPrice['package_size'],
                'unit_name'              => $unitPrice['unit_name'],
                'remaining_qty'          => ($this->storeId != 1 || $remainingQty > 0) ? $remainingQty : 0,
                'remaining_quantity_base' => $remainingBaseQtyUnit,
                'base_unit_id'           => $baseUnitPrice?->unit_id,
                'base_unit_name'         => $baseUnitPrice?->unit?->name,
                'minimum_quantity'       => $unitPrice['minimum_quantity'],
                'is_last_unit'           => $unitPrice['is_last_unit'],
                'is_largest_unit'        => $unitPrice['is_largest_unit'],
                'price'                  => $priceFromInventory,
                'price_source'           => $priceSource,
                'price_store_id'         => $priceStoreId,
            ];
        }

        return $result;
    }


    public function getProductUnitPrices($productId): Collection
    {
        if (!$productId) {
            return collect();
        }

        $product = Product::find($productId);

        if (!$product) {
            return collect();
        }

        $query = $product->reportUnitPrices()
            ->orderBy('order', 'asc')
            ->with('unit');

        if ($this->unitId !== 'all') {
            if (is_array($this->unitId)) {
                $query->whereIn('unit_id', $this->unitId);
            } elseif (is_numeric($this->unitId)) {
                $query->where('unit_id', $this->unitId);
            }
        }

        $productUnitPrices = $query->get(['unit_id', 'order', 'price', 'package_size', 'minimum_quantity']);

        $maxOrder       = $productUnitPrices->max('order');
        $maxPackageSize = $productUnitPrices->max('package_size');

        // Fetched once here — avoids a DB query per iteration inside map()
        $minStockQty = $product->minimum_stock_qty ?? 0;

        return $productUnitPrices->map(function ($unitPrice) use ($maxOrder, $maxPackageSize, $minStockQty) {
            return [
                'unit_id'          => $unitPrice->unit_id,
                'order'            => $unitPrice->order,
                'package_size'     => $unitPrice->package_size,
                'unit_name'        => $unitPrice->unit->name,
                'minimum_quantity' => $unitPrice->order == $maxOrder ? $minStockQty : 0,
                'is_last_unit'     => $unitPrice->order == $maxOrder,
                'is_largest_unit'  => $unitPrice->package_size == $maxPackageSize,
                'price'            => $unitPrice->price,
                'is_fractional'    => $unitPrice->unit->is_fractional ?? true,
            ];
        });
    }


    public function getProductsBelowMinimumQuantity(): array
    {
        $inventory       = $this->getInventoryReport();
        $lowStockProducts = [];

        foreach ($inventory['reportData'] as $productData) {
            foreach ($productData as $product) {
                if ($product['is_last_unit'] === true && $product['remaining_qty'] <= $product['minimum_quantity']) {
                    $lowStockProducts[] = $product;
                }
            }
        }

        return $lowStockProducts;
    }


    public function getProductsBelowMinimumQuantityًWithPagination($perPage = 15, $active = false): LengthAwarePaginator
    {
        if ($active) {
            $this->isActive = true;
        }

        $inventory        = $this->getInventoryReport()['reportData'];
        $lowStockProducts = [];

        foreach ($inventory as $productData) {
            foreach ($productData as $product) {
                if ($product['is_largest_unit'] === true && $product['remaining_qty'] <= $product['minimum_quantity']) {
                    $lowStockProducts[] = $product;
                }
            }
        }

        $currentPage = request()->get('page', 1);
        $collection  = new Collection($lowStockProducts);
        $pagedData   = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $totalPages  = (int) ceil($collection->count() / $perPage);

        return new LengthAwarePaginator($pagedData, $collection->count(), $perPage, $currentPage, [
            'path'       => request()->url(),
            'query'      => request()->query(),
            'totalPages' => $totalPages,
        ]);
    }


    public function getLastUnitByPackageSize($productId): ?UnitPrice
    {
        return UnitPrice::where('product_id', $productId)
            ->with('unit')
            ->orderByDesc('package_size')
            ->first();
    }

    public function getSmallestUnitByPackageSize($productId): ?UnitPrice
    {
        return UnitPrice::where('product_id', $productId)
            ->with('unit')
            ->orderBy('package_size', 'asc')
            ->first();
    }


    public function getInventoryIn($productId): array
    {
        $totalIn = InventoryTransaction::query()
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where('product_id', $productId)
            ->sum(DB::raw('quantity * package_size'));

        $unitPrices = $this->getProductUnitPrices($productId);
        $product    = Product::find($productId);
        $result     = [];

        foreach ($unitPrices as $unitPrice) {
            $packageSize = max($unitPrice['package_size'] ?? 1, 1);

            $result[] = [
                'product_id'   => $productId,
                'product_name' => $product->name,
                'unit_id'      => $unitPrice['unit_id'],
                'order'        => $unitPrice['order'],
                'package_size' => $unitPrice['package_size'],
                'unit_name'    => $unitPrice['unit_name'],
                'quantity'     => round($totalIn / $packageSize, 4),
                'is_last_unit' => $unitPrice['is_last_unit'],
            ];
        }

        return $result;
    }


    public function getInventoryOut($productId): array
    {
        $totalOut = InventoryTransaction::query()
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->where('product_id', $productId)
            ->sum(DB::raw('quantity * package_size'));

        $unitPrices = $this->getProductUnitPrices($productId);
        $product    = Product::find($productId);
        $result     = [];

        foreach ($unitPrices as $unitPrice) {
            $packageSize = max($unitPrice['package_size'] ?? 1, 1);

            $result[] = [
                'product_id'   => $productId,
                'product_name' => $product->name,
                'unit_id'      => $unitPrice['unit_id'],
                'order'        => $unitPrice['order'],
                'package_size' => $unitPrice['package_size'],
                'unit_name'    => $unitPrice['unit_name'],
                'quantity'     => round($totalOut / $packageSize, 4),
                'is_last_unit' => $unitPrice['is_last_unit'],
            ];
        }

        return $result;
    }


    public function setProductIds(array $productIds): void
    {
        $this->productIds = $productIds;
    }


    public static function getRemainingQty(int $productId, int $unitId, int $storeId): float
    {
        return (new self(null, $productId, $unitId, $storeId))
            ->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0.0;
    }

    public static function quickReport(int $storeId, int $productId, ?int $unitId = null): array
    {
        return (new self(null, $productId, $unitId, $storeId))
            ->getInventoryReport();
    }
}
