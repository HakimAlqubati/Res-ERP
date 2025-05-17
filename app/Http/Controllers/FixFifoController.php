<?php

namespace App\Http\Controllers;

use App\Services\FixFifo\FifoAllocatorService;
use Illuminate\Http\Request;

class FixFifoController extends Controller
{
    public function fix(Request $request)
    {
        $productId = $request->product_id ?? 0;


        $allocator = new FifoAllocatorService();
        $allocations = $allocator->allocate($productId);

        $result = [
            'count' => count($allocations),
            'data' => $allocations
        ];
        return response()->json($result);
    }

    public function fixFifoWithSave(Request $request)
    {
        $productsString = $request->query('products', '');

        // تحويلها إلى مصفوفة مع إزالة الفراغات والتكرار
        $productIds = collect(explode(',', $productsString))
            ->map(fn($id) => (int) trim($id))
            ->filter()       // إزالة القيم الفارغة أو الصفرية
            ->unique()       // إزالة التكرارات
            ->values()
            ->all();
            
        foreach ($productIds as $productId) {
            $allocator = new FifoAllocatorService();
            $allocations = $allocator->allocate($productId);
            FifoAllocatorService::saveAllocations($allocations, $productId);
        }

        return response()->json([
            'message' => 'FIFO allocation and saving completed.',
            'product_ids' => $productIds,
        ]);
    }
}
