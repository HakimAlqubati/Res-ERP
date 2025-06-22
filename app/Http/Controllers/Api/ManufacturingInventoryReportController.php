<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Inventory\ManufacturingInventoryDetailReportService;

class ManufacturingInventoryReportController extends Controller
{
    protected $service;

    public function __construct(ManufacturingInventoryDetailReportService $service)
    {
        $this->service = $service;
    }

    public function show(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'store_id' => 'required|integer|exists:stores,id',
            'only_smallest_unit' => 'nullable|boolean',
        ]);

        $data = $this->service->getDetailedRemainingStock(
            $request->product_id,
            $request->store_id,
            $request->boolean('only_smallest_unit')
        );

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}
