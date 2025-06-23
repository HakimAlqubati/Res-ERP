<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\InventoryDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryDashboardController extends Controller
{
    public function getSummary(InventoryDashboardService $service): JsonResponse
    {
        $data = $service->getSummary();
        return response()->json($data);
    }
}
