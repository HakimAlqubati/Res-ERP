<?php

// app/Http/Controllers/Api/ProductPriceHistoryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ManufacturedProductPriceHistoryService;
use App\Services\ManufacturedProductPriceUpdaterService;
use App\Services\ProductPriceHistoryService;
use Illuminate\Http\Request;

class ProductPriceHistoryController extends Controller
{
    protected ProductPriceHistoryService $service;

    public function __construct(ProductPriceHistoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $productId = $request->get('product_id'); // اختياري
        $history = $this->service->getPriceHistory($productId);

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }
    public function updateAllManufacturedPrices()
    {
        $service = new ManufacturedProductPriceUpdaterService();
        $history = $service->updateAll();
        return $history;
    }
    public function manufacturingProductPriceHistory(Request $request)
    {
        $productId = $request->get('product_id'); // اختياري
        $service = new ManufacturedProductPriceUpdaterService();
        $history = $service->updateSingle($productId);

        if (!$history) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Product not found or no data updated.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }
}
