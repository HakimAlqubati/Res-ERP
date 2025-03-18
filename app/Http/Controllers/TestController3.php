<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Product;
use App\Services\FifoInventoryService;
use App\Services\Firebase\FcmClient;
use App\Services\InventoryService;
use Carbon\Carbon;
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

    public function sendFCM(Request $request)
    {
        return sendNotification(
            $request->token,
            $request->title,
            $request->body
        );
    }
    public function printStock()
    {

        $categoryId = request()->query()['category_id'] ?? null;
        $products = Product::active()
            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->with(['unitPrices.unit']) // Load unit name
            ->get(['name', 'category_id', 'id', 'code']);
        return view('filament.clusters.inventory-management-cluster.resources.stock-inventory-resource.pages.stock-print', compact('products'));
    }
    public function getEmployeesWithOddAttendances($startDate = null, $endDate = null,$branchId= null)
    {
        if (!$startDate && !$endDate) {
            $startDate = $_GET['start_date'];
            $endDate = $_GET['end_date'];
            $branchId = 8;
        }
    
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
    
        // Initialize an array to hold the result data
        $result = [];
    
        // Loop through the date range and get attendance data for each day
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            // Fetch employees with odd number of attendances for the current date
            $employees = Employee::with(['attendances' => function ($query) use ($currentDate) {
                $query->where('check_date', $currentDate)->where('accepted', 1)
                    ->select('id', 'check_date', 'check_time', 'check_type', 'employee_id','accepted','period_id'); // Only selected fields from attendances
            }])
                ->whereHas('attendances', function ($query) use ($currentDate) {
                    $query->where('check_date', $currentDate)->where('accepted', 1)
                        ->selectRaw('count(*) as attendance_count')
                        ->groupBy('employee_id')
                        ->havingRaw('COUNT(*) % 2 != 0'); // Odd number of attendances
                })
                ->where('branch_id', $branchId) // Optional: Add branch filter
                ->select('id', 'name') // Only select id and name
                ->get();
    
            // For each employee, predict the next expected check-in or check-out
            foreach ($employees as $employee) {
                $lastAttendance = $employee->attendances->last();
    
                // Predict next attendance (check-in or check-out)
                $prediction = null;
                if ($lastAttendance) {
                    if ($lastAttendance->check_type == 'checkin') {
                        $prediction = 'checkout';  // If last attendance was check-in, predict checkout next
                    } elseif ($lastAttendance->check_type == 'checkout') {
                        $prediction = 'checkin';  // If last attendance was check-out, predict checkin next
                    }
                }
    
                // Add the prediction to the employee data
                $employee->prediction = $prediction;
            }
    
            // Add the data for the current date (group by date)
            $result[$currentDate->toDateString()] = [
                'employees' => $employees
            ];
    
            // Move to the next date
            $currentDate->addDay();
        }
    
        return $result;
        // Return the grouped result
        return response()->json($result);
    }
    
}
