<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Product;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;

class BatchReportController extends Controller
{
    public function index(Request $request, InventoryStockRepositoryInterface $inventoryRepo)
    {
        $storeId = $request->input('store_id');
        $productId = $request->input('product_id');

        $batches = collect();

        if ($storeId && $productId) {
            $batches = $inventoryRepo->getAvailableStockBatches(
                productId: (int) $productId,
                storeId: (int) $storeId
            );
        }

        $stores = Store::active()->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('reports.batch_report', compact('batches', 'stores', 'products', 'storeId', 'productId'));
    }
}
