<?php

namespace App\Http\Controllers;

use App\Services\FifoInventoryService;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class TestController3 extends Controller
{
    public function testInventory()
    {
        $productId = 1;
        $unitId = null;
        $inventoryService = new InventoryService($productId, $unitId);

        // Get report for a specific product and unit
        $report = $inventoryService->getInventoryReport();

        // Print or return the report as JSON
        return response()->json($report);
    }


    public function testFifo()
    {
        $productId = $_GET['p'];
        $unitId = $_GET['u'];
        $requestedQuantity = $_GET['q'];
        $fifoService = new FifoInventoryService($productId, $unitId, $requestedQuantity);
        $response = $fifoService->allocateOrder();
        return $response;
    }
}
