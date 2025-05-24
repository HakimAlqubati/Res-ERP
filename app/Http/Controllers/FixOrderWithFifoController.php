<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\FifoMethodService;
use App\Services\OrderInventoryFixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixOrderWithFifoController extends Controller
{

    public function fixInventoryForReadyOrder($orderId)
    {
        $order = Order::with(['orderDetails', 'branch.store'])->findOrFail($orderId);
        $service = new OrderInventoryFixService();

        $result = $service->fixInventoryForOrder($order);
        return response()->json($result, $result['status'] === 'error' ? 400 : 200);
    }

    public function getAllocationsPreview($orderId)
    {
        $order = Order::with('orderDetails')->findOrFail($orderId);
        $service = new OrderInventoryFixService();

        $result = $service->previewAllocations($order);
        return response()->json($result, $result['status'] === 'error' ? 400 : 200);
    }
}
