<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\MultiProductsInventoryService;
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

    public function inventoryReport(Request $request)
    {
        $productId = $request->product_id ?? null;
        $storeId = $request->store_id ?? null;
        $categoryId = $request->category_id ?? null;
        $unitId = 'all';
        if (!empty($request->unit_id)) {
            $unitId = $request->unit_id;
        }
        $inventoryService = new MultiProductsInventoryService($categoryId, $productId, $unitId, $storeId);
        
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
}
