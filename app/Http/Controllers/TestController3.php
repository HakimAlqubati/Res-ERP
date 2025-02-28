<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Product;
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

    public function testQRCode($id)
    {
        $equipment = Equipment::findOrFail($id); // Fetch all equipment

        $qrCode = [
            'id' => $equipment->id,
            'data' => url('/') . '/admin/h-r-service-request/equipment/' . $equipment->id,
            'name' => $equipment->name
        ];

        return view('qr-code.qrcode', compact('qrCode'));
    }

    public function currntStock()
    {
        $inventoryService = new \App\Services\MultiProductsInventoryService();
        $currentStock = $inventoryService->getInventoryReport();
        return $currentStock;
    }
    public function lowStock()
    {
        $inventoryService = new \App\Services\MultiProductsInventoryService();
        $lowStockProducts = $inventoryService->getProductsBelowMinimumQuantity();
        // dd($lowStockProducts);
        return $lowStockProducts;
    }

    public function getProductItems($id)
    {
        $manufacturingService = new \App\Services\Products\Manufacturing\ProductManufacturingService();
        $response = $manufacturingService->getProductItems($id);
        // dd($response['product_items'],$response['unit_prices']);
        return $response;
    }
}
