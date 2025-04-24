<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\BranchOrderSupplyReportService;
use App\Services\MultiProductsInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function minimumStockReport()
    {
        $inventoryService = new \App\Services\MultiProductsInventoryService();
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantityًWithPagination();

        return response()->json([
            'data' => $lowStockProducts,
            'totalPages' => $lowStockProducts->lastPage(),
        ]);
    }


    public function minimumStockReportToSupply()
    {
        $inventoryService = new \App\Services\MultiProductsInventoryService();
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantityًWithPagination(1000);
        // return $lowStockProducts;
        foreach ($lowStockProducts as $product) {
            InventoryTransaction::create([
                'product_id' => $product['product_id'],
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => 1000,
                'unit_id' => $product['unit_id'],
                'movement_date' => Carbon::now(),
                'package_size' => $product['package_size'],
                'price' => $product['price'],
                'transaction_date' => Carbon::now(),
                'notes' => 'Stock supply for minimum stock products',
                'transactionable_id' => $product['product_id'],
                'transactionable_type' => 'ProductImport',
                'store_id' => 1,
                'waste_stock_percentage' => 0,
            ]);
        }
        return response()->json([
            'data' => $lowStockProducts,
            'totalPages' => $lowStockProducts->lastPage(),
        ]);
    }

    public function inventoryReport(Request $request)
    {
        $productId = $request->product_id ?? null;
        $storeId = $request->store_id ?? null;
        if (isset(auth()->user()->branch) && auth()->user()->branch->is_kitchen) {
            $storeId = auth()->user()->branch->store_id;
        };
        $categoryId = $request->category_id ?? null;
        $unitId = 'all';
        if (!empty($request->unit_id)) {
            $unitId = $request->unit_id;
        }
        $showAvailableInStock = 0;
        if (isset($request->showAvailableInSock) && $request->showAvailableInSock) {
            $showAvailableInStock = 1;
        }
        $inventoryService = new MultiProductsInventoryService($categoryId, $productId, $unitId, $storeId, $showAvailableInStock);

        // Get paginated report data
        $report = $inventoryService->getInventoryReportWithPagination(15);

        return response()->json($report);
    }

    public function productTracking(Request $request)
    {
        $productId = $request->product_id ?? null;

        $product = Product::find($productId);

        $reportData = collect();

        if (!empty($productId)) {
            $rawData = InventoryTransaction::getInventoryTrackingDataPagination($productId, 15);
            $reportData = $rawData->through(function ($item) {

                $item->formatted_transactionable_type = class_basename($item->transactionable_type);
                $item->unit->name;
                $item->movement_date = \Carbon\Carbon::parse($item->movement_date)->format('Y-m-d'); // force it here
                $item->transaction_date = \Carbon\Carbon::parse($item->transaction_date)->format('Y-m-d'); // force it here
                return $item;
            });
        }

        return ['reportData' => $reportData, 'product' => $product, 'totalPages' => $rawData->lastPage()];
    }
    public function filters()
    {
        $filters = [

            'categories' => \App\Models\Category::active()->pluck('name', 'id')->toArray(),
            'stores' => \App\Models\Store::active()
                ->centralKitchenStores()
                ->pluck('name', 'id')->toArray(),
        ];

        return response()->json($filters);
    }
    public function branchQuantities()
    {
        $service = new BranchOrderSupplyReportService();
        $branchId = request('branch_id');
        $productId = request('product_id');
        $result = $service->branchQuantities($branchId, $productId);
        $res = ['reportData' => $result];
        return response()->json($res);
    }
}
