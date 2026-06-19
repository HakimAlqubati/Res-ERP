<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Product;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\GetAvailableStockBatchesQueryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;

class BatchReportController extends Controller
{
    public function __construct(
        private readonly InventoryStockRepositoryInterface $stockRepository,
        private readonly GetAvailableStockBatchesQueryInterface $stockBatchesQuery,
    ) {}

    public function index(Request $request)
    {
        $storeId = $request->input('store_id');
        $productId = $request->input('product_id');

        $batches = collect();

        if ($storeId) {
            $productIds = $request->input('product_ids', []);
            // backwards-compatible: single product_id → wrap in array
            if (empty($productIds) && $productId) {
                $productIds = [(int) $productId];
            }

            $batches = $this->stockBatchesQuery->execute(
                new StockBatchFilterDTO(
                    storeId: (int) $storeId,
                    productIds: array_map('intval', $productIds),
                    perPage: $request->filled('per_page') ? (int) $request->input('per_page') : null,
                )
            );
        }

        $stores = Store::active()->orderBy('name')->get();
        $products = Product::orderBy('id')->get();

        return view('reports.batch_report', compact('batches', 'stores', 'products', 'storeId', 'productId'));
    }
}

