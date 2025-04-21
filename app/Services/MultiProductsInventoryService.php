<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MultiProductsInventoryService
{
    public $productId;
    public $unitId;
    public $storeId;
    public $categoryId;
    public $filterOnlyAvailable;

    public function __construct($categoryId = null, $productId = null, $unitId = 'all', $storeId = null, $filterOnlyAvailable = false)
    {
        $this->categoryId = $categoryId;
        $this->productId = $productId;
        $this->unitId = $unitId;
        $this->storeId = $storeId;
        $this->filterOnlyAvailable = $filterOnlyAvailable;
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
            $report = $this->getInventoryForProduct($product->id);
        }
        return [
            'reportData' => $report,
            'pagination' => $products, // Pass pagination data
        ];
    }


    public function getInventoryReportWithPagination($perPage = 15)
    {
        $query = Product::query();

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        // الحالة الأولى: عندك منتج محدد
        if ($this->productId) {
            return [
                'reportData' => [$this->getInventoryForProduct($this->productId)],
                'pagination' => null,
                'totalPages' => 1,
            ];
        }

        // الحالة الثانية: فلترة على المنتجات المتوفرة فقط → نلغي pagination من البداية
        if ($this->filterOnlyAvailable) {
            $allProducts = $query->get(); // جلب كل المنتجات

            $filteredReport = [];

            foreach ($allProducts as $product) {
                $productInventory = $this->getInventoryForProduct($product->id);
                $totalRemaining = collect($productInventory)->sum('remaining_qty');

                if ($totalRemaining > 0) {
                    $filteredReport[] = $productInventory;
                }
            }

            // تحويلهم إلى Collection وتطبيق pagination يدويًا
            $currentPage = request()->get('page', 1);
            $collection = collect($filteredReport);
            $pagedData = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $pagination = new LengthAwarePaginator($pagedData, $collection->count(), $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return [
                'reportData' => $pagedData,
                'pagination' => $pagination,
                'totalPages' => $pagination->lastPage(),
            ];
        }

        // الحالة الثالثة: بدون فلترة → استخدم pagination العادي
        $products = $query->paginate($perPage);
        $report = [];

        foreach ($products as $product) {
            $report[] = $this->getInventoryForProduct($product->id);
        }

        return [
            'reportData' => $report,
            'pagination' => $products,
            'totalPages' => $products->lastPage(),
        ];
    }


    public function getInventoryForProduct($productId)
    {
        $queryIn = DB::table('inventory_transactions')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN);

        $queryOut = DB::table('inventory_transactions')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT);

        // جلب آخر سعر لكل وحدة من جدول المخزون (MOVEMENT_IN فقط)
        $transactionPrices = DB::table('inventory_transactions')
            ->select('unit_id', DB::raw('MAX(id) as last_id'))
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->groupBy('unit_id')
            ->pluck('last_id', 'unit_id');

        $unitTransactions = [];

        if ($transactionPrices->count()) {
            $unitTransactionsRaw = DB::table('inventory_transactions')
                ->whereIn('id', $transactionPrices->values())
                ->get(['unit_id', 'price', 'store_id']);

            foreach ($unitTransactionsRaw as $row) {
                $unitTransactions[$row->unit_id] = [
                    'price' => $row->price,
                    'store_id' => $row->store_id,
                ];
            }
        }

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
            $packageSize = max($unitPrice['package_size'] ?? 1, 1); // يضمن عدم القسمة على صفر
            $remainingQty = round($remQty / $packageSize, 2);

            // نحاول نجيب السعر من المخزون حسب الوحدة
            $unitId = $unitPrice['unit_id'];

            $priceFromInventory = $unitTransactions[$unitId]['price'] ?? null;
            $priceStoreId = $unitTransactions[$unitId]['store_id'] ?? null;
            $priceSource = 'inventory';

            if (is_null($priceFromInventory)) {
                $firstUnitPrice = $unitPrices->firstWhere('order', 1);
                if ($firstUnitPrice && isset($unitTransactions[$firstUnitPrice['unit_id']])) {
                    $basePackageSize = max($firstUnitPrice['package_size'] ?? 1, 1);
                    $basePrice = $unitTransactions[$firstUnitPrice['unit_id']]['price'];
                    $priceStoreId = $unitTransactions[$firstUnitPrice['unit_id']]['store_id'];
                    $priceFromInventory = round(($packageSize / $basePackageSize) * $basePrice, 2);
                    $priceSource = 'inventory (calculated)';
                }
            }

            if (is_null($priceFromInventory)) {
                $priceFromInventory = $unitPrice['price'] ?? null;
                $priceSource = 'unit_price';
                $priceStoreId = null; // ما في مصدر مخزن في هذه الحالة
            }
            $result[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'unit_id' => $unitPrice['unit_id'],
                'order' => $unitPrice['order'],
                'package_size' => $unitPrice['package_size'],
                'unit_name' => $unitPrice['unit_name'],
                'remaining_qty' => $remainingQty,
                'minimum_quantity' => $unitPrice['minimum_quantity'],
                'is_last_unit' => $unitPrice['is_last_unit'],
                'price' => $priceFromInventory,
                'price_source' => $priceSource,
                'price_store_id' => $priceStoreId,

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

        $productUnitPrices = $query->get(['unit_id', 'order', 'price', 'package_size', 'minimum_quantity']);
        // Find the highest order value to determine the last unit
        $maxOrder = $productUnitPrices->max('order');

        return $productUnitPrices->map(function ($unitPrice) use ($maxOrder, $query) {
            $minimumQty = 0;
            if ($unitPrice->order == $maxOrder) {
                $minimumQty = $query->first()->product->minimum_stock_qty ?? 0;
            }
            return [
                'unit_id' => $unitPrice->unit_id,
                'order' => $unitPrice->order,
                'package_size' => $unitPrice->package_size,
                'unit_name' => $unitPrice->unit->name,
                'minimum_quantity' => $minimumQty,
                'is_last_unit' => $unitPrice->order == $maxOrder, // True if this is the last unit
                'price' => $unitPrice->price,
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
        return $lowStockProducts;
    }



    public function getProductsBelowMinimumQuantityًWithPagination($perPage = 15)
    {
        $inventory = $this->getInventoryReport();
        $lowStockProducts = [];
        foreach ($inventory as $productData) {
            foreach ($productData as $product) {
                if ($product['is_last_unit'] == true && $product['remaining_qty'] <= $product['minimum_quantity']) {
                    $lowStockProducts[] = $product;
                }
            }
        }


        // Paginate results
        $currentPage = request()->get('page', 1);
        $collection = new Collection($lowStockProducts);
        $pagedData = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();


        $total = count($collection);
        $totalPages = ceil($total / $perPage);
        return new LengthAwarePaginator($pagedData, count($collection), $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
            'totalPages' => $totalPages,
        ]);
    }



    public function allocateFIFO($productId, $unitId, $requestedQty, $sourceModel = null)
    {

        $inventoryReportProduct = $this->getInventoryForProduct($productId);
        $inventoryRemainingQty = collect($inventoryReportProduct)->firstWhere('unit_id', $unitId)['remaining_qty'] ?? 0;
        $targetUnit = \App\Models\UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)->with('unit')
            ->first();
        if (!$targetUnit) {
            Log::info("❌ Unit ID: $unitId not found for product ID: $productId.");
            throw new \Exception("❌ Unit ID: $unitId not found for product ID: $productId.");
        }
        if ($requestedQty > $inventoryRemainingQty) {
            if (setting('create_auto_order_when_stock_empty')) {
                // 🚀 إنشاء الطلب الجديد
                $newOrder = \App\Models\Order::create([
                    'customer_id' => $sourceModel->customer_id,
                    'branch_id' => $sourceModel->branch_id,
                    'status' => \App\Models\Order::PENDING_APPROVAL,
                    'order_date' => now(),
                    'type' => \App\Models\Order::TYPE_NORMAL,
                    'notes' => "Auto-generated due to stock unavailability from Order #{$sourceModel?->id}",
                ]);

                // نسخة من التفاصيل بنفس الكمية
                $newOrder->orderDetails()->create([
                    'product_id' => $productId,
                    'unit_id' => $unitId,
                    'quantity' => $requestedQty,
                    'price' => getUnitPrice($productId, $unitId),
                    'package_size' => $targetUnit->package_size,
                    'created_by' => auth()->id(),
                    'is_created_due_to_qty_preivous_order' => true,
                    'previous_order_id' => $sourceModel->id,
                ]);

                // تصفير الكمية في الطلب الأصلي
                if ($sourceModel) {
                    $sourceModel->orderDetails()
                        ->where('product_id', $productId)
                        ->where('unit_id', $unitId)
                        ->update(['available_quantity' => 0]);
                }

                Log::info("✅ Created pending approval order #{$newOrder->id} due to stock unavailability.");

                return []; // لا تخصص شيء للطلب الأصلي
            } else {
                $productName = $targetUnit->product->name ?? 'Unknown Product';
                $unitName = $targetUnit->unit->name ?? 'Unknown Unit';
                Log::info("❌ Requested quantity ($requestedQty) exceeds available inventory ($inventoryRemainingQty) for product: $productName (unit: $unitName)");
                throw new \Exception("❌ Requested quantity ($requestedQty) exceeds available inventory ($inventoryRemainingQty) for product: $productName");
            }
        }
        $allocations = [];
        $entries = InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();
        $qtyBasedOnUnit = 0;
        foreach ($entries as $entry) {
            $previousOrderedQtyBasedOnTargetUnit = InventoryTransaction::where('source_transaction_id', $entry->id)
                ->where('product_id', $productId)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->whereNull('deleted_at')
                ->sum(DB::raw('quantity')) / $targetUnit->package_size;

            $entryQty = $entry->quantity;

            foreach ($this->getProductUnitPrices($productId) as $key => $value) {
                if ($value['unit_id'] == $unitId) {
                    $qtyBasedOnUnit = (($entryQty * $entry->package_size) / $targetUnit->package_size) - $previousOrderedQtyBasedOnTargetUnit;
                }
            }
            $deductQty = min($requestedQty, $qtyBasedOnUnit);

            if ($qtyBasedOnUnit <= 0) {
                continue;
            }
            if ($requestedQty <= 0) {
                break;
            }

            $price = ($entry->price / $entry->package_size) * $targetUnit->package_size;
            $allocations[] = [
                'transaction_id' => $entry->id,
                'store_id' => $entry->store_id,
                'unit_id' => $entry->unit_id,
                'target_unit_id' => $unitId,
                'target_unit_package_size' => $targetUnit->package_size,
                'entry_price' => $entry->price,
                'price_based_on_unit' => $price,
                'package_size' => $entry->package_size,
                'movement_date' => $entry->movement_date,
                'transactionable_id' => $entry->transactionable_id,
                'transactionable_type' => $entry->transactionable_type,
                'entry_qty' => $entryQty,
                'entry_qty_based_on_unit' => $qtyBasedOnUnit,
                'deducted_qty' => $deductQty,
                'previous_ordered_qty_based_on_unit' => $previousOrderedQtyBasedOnTargetUnit,
            ];

            $requestedQty -= $deductQty;
        }

        return $allocations;
    }

    public function getLastUnitByPackageSize($productId)
    {
        return \App\Models\UnitPrice::where('product_id', $productId)
            ->with('unit')
            ->orderByDesc('package_size')
            ->first();
    }
    public function getSmallestUnitByPackageSize($productId)
    {
        return \App\Models\UnitPrice::where('product_id', $productId)
            ->with('unit')
            ->orderBy('package_size', 'asc')
            ->first();
    }
    public function getInventoryIn($productId)
    {
        $queryIn = \App\Models\InventoryTransaction::query()
            ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at')
            // ->with(['product', 'unit', 'store'])
            // ->orderBy('movement_date', 'desc')
        ;
        $queryIn->where('product_id', $productId);
        $totalIn = $queryIn->sum(DB::raw('quantity * package_size'));
        $unitPrices = $this->getProductUnitPrices($productId);
        $product = Product::find($productId);
        $result = [];

        foreach ($unitPrices as $unitPrice) {
            $packageSize = max($unitPrice['package_size'] ?? 1, 1);
            $totalInRes = round($totalIn / $packageSize, 2);

            $result[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'unit_id' => $unitPrice['unit_id'],
                'order' => $unitPrice['order'],
                'package_size' => $unitPrice['package_size'],
                'unit_name' => $unitPrice['unit_name'],
                'quantity' => $totalInRes,
                'is_last_unit' => $unitPrice['is_last_unit'],
            ];
        }

        return $result;
    }
    public function getInventoryOut($productId)
    {

        $queryOut = DB::table('inventory_transactions')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT);

        $totalOut = $queryOut->sum(DB::raw('quantity * package_size'));
        $unitPrices = $this->getProductUnitPrices($productId);
        $product = Product::find($productId);
        $result = [];

        foreach ($unitPrices as $unitPrice) {
            $packageSize = max($unitPrice['package_size'] ?? 1, 1); // يضمن عدم القسمة على صفر
            $totalOut = round($totalOut / $packageSize, 2);

            $result[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'unit_id' => $unitPrice['unit_id'],
                'order' => $unitPrice['order'],
                'package_size' => $unitPrice['package_size'],
                'unit_name' => $unitPrice['unit_name'],
                'quantity' => $totalOut,
                'is_last_unit' => $unitPrice['is_last_unit'],
            ];
        }

        return $result;
    }
}
