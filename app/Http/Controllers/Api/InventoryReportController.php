<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Store;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\BranchOrderSupplyReportService;
use App\Services\MultiProductsInventoryPurchasedService;
use App\Services\MultiProductsInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function minimumStockReport()
    {
        $inventoryService = new MultiProductsInventoryService(storeId: getDefaultStore());
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantityًWithPagination();

        return response()->json([
            'data'       => $lowStockProducts,
            'totalPages' => $lowStockProducts->lastPage(),
        ]);
    }

    public function minimumStockReportToSupply()
    {
        $inventoryService = new MultiProductsInventoryService(storeId: getDefaultStore());
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantityًWithPagination(1000);
        return $lowStockProducts;
        foreach ($lowStockProducts as $product) {
            InventoryTransaction::create([
                'product_id'             => $product['product_id'],
                'movement_type'          => InventoryTransaction::MOVEMENT_IN,
                'quantity'               => 1000,
                'unit_id'                => $product['unit_id'],
                'movement_date'          => Carbon::now(),
                'package_size'           => $product['package_size'],
                'price'                  => $product['price'],
                'transaction_date'       => Carbon::now(),
                'notes'                  => 'Stock supply for minimum stock products',
                'transactionable_id'     => $product['product_id'],
                'transactionable_type'   => 'ProductImport',
                'store_id'               => 1,
                'waste_stock_percentage' => 0,
            ]);
        }
        return response()->json([
            'data'       => $lowStockProducts,
            'totalPages' => $lowStockProducts->lastPage(),
        ]);
    }

    public function inventoryReport(Request $request)
    {
        $productId = $request->product_id ?? null;
        $storeId   = $request->store_id ?? null;
        if (isset(auth()->user()->branch) && auth()->user()->branch->is_kitchen) {
            $storeId = auth()->user()->branch->store_id;
        }
        $categoryId = $request->category_id ?? null;
        $unitId     = 'all';
        if (! empty($request->unit_id)) {
            $unitId = $request->unit_id;
        }
        $showAvailableInStock = 0;
        if (isset($request->showAvailableInSock) && $request->showAvailableInSock) {
            $showAvailableInStock = 1;
        }
        $inventoryService = new MultiProductsInventoryService($categoryId, $productId, $unitId, $storeId, $showAvailableInStock);

        // ✅ دعم productIds لو كان موجود في الـ request
        if ($request->has('product_ids')) {
            $productIdsRaw = $request->product_ids;
            if (is_string($productIdsRaw)) {
                // يحول "1,2,3" إلى [1, 2, 3]
                $productIds = array_map('trim', explode(',', $productIdsRaw));
            } elseif (is_array($productIdsRaw)) {
                $productIds = $productIdsRaw;
            } else {
                $productIds = [];
            }

            $inventoryService->setProductIds($productIds);
        }

        // Get paginated report data
        $report = $inventoryService->getInventoryReportWithPagination(15);

        return response()->json($report);
    }

    public function productTracking(Request $request)
    {
        $productId = $request->product_id ?? null;

        $product      = Product::find($productId);
        $movementType = $request->movement_type ?? null;
        $storeId      = $request->store_id ?? null;
        $reportData   = collect();

        $rawData = null;
        if (! empty($productId)) {
            $rawData = InventoryTransaction::getInventoryTrackingDataPagination(
                $productId,
                15,
                $movementType,
                null,
                $storeId
            );
            $reportData = $rawData->through(function ($item) {

                $item->formatted_transactionable_type = class_basename($item->transactionable_type);
                $item->unit->name;
                $item->movement_date    = Carbon::parse($item->movement_date)->format('Y-m-d'); // force it here
                $item->transaction_date = Carbon::parse($item->transaction_date)->format('Y-m-d');
                $item->quantity         = formatQunantity($item->quantity);
                $item->store = $item?->store?->name ?? '';
                return $item;
            });
        }

        return ['reportData' => $reportData, 'product' => $product, 'totalPages' => $rawData ? $rawData->lastPage() : 0];
    }
    public function filters()
    {
        $stores = Store::query()
            // يوجد فروع تصنيعية
            ->whereHas('branches', fn($q) => $q->where('type', Branch::TYPE_CENTRAL_KITCHEN)
                ->whereNull('deleted_at'))
            // لا يوجد فروع غير تصنيعية
            ->whereDoesntHave('branches', fn($q) => $q->where('type', '!=', Branch::TYPE_CENTRAL_KITCHEN)
                ->whereNull('deleted_at'))
                ->orWhereDoesntHave('branches')->where('default_store',1)
            ->get()->pluck('name', 'id')->toArray();

        $filters = [

            'categories'     => Category::active()->pluck('name', 'id')->toArray(),
            'stores'         => $stores,
            'movement_types' => InventoryTransaction::getMovementTypes(),
            'manufacturing_filter' => collect(\App\Enums\ProductType::cases())
                ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                ->toArray(),
        ];

        return response()->json($filters);
    }
    public function branchQuantities()
    {
        $service   = new BranchOrderSupplyReportService();
        $branchId  = request('branch_id');
        $productId = request('product_id');
        $result    = $service->branchQuantities($branchId, $productId);
        $res       = ['reportData' => $result];
        return response()->json($res);
    }

    public function testInventoryPurchasedReport()
    {
        $inventoryService = new MultiProductsInventoryPurchasedService();

        // Get paginated report data
        $report = $inventoryService->getInventoryReportWithPagination(15);

        return response()->json([$report]);
    }
}
