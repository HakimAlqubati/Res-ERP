<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Product;
use App\Services\FifoInventoryService;
use App\Services\Firebase\FcmClient;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function getEmployeesWithOddAttendances($startDate = null, $endDate = null, $branchId = null)
    {
        if (!$startDate && !$endDate) {
            $startDate = $_GET['start_date'];
            $endDate = $_GET['end_date'];
            $branchId = 5;
        }

        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        // Initialize an array to hold the result data
        $result = [];

        // Loop through the date range and get attendance data for each day
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $oddEmployeeIds = DB::table('hr_attendances')
                ->select('employee_id')
                ->whereDate('check_date', $currentDate)
                ->where('accepted', 1)
                ->groupBy('employee_id')
                ->havingRaw('COUNT(*) % 2 != 0')
                ->pluck('employee_id');

            $employees = Employee::with(['attendances' => function ($query) use ($currentDate) {
                $query->where('check_date', $currentDate)
                    ->where('accepted', 1)
                    ->select('id', 'check_date', 'check_time', 'check_type', 'employee_id', 'accepted', 'period_id');
            }])
                ->whereIn('id', $oddEmployeeIds)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->select('id', 'name')
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
    public function testGetBranches()
    {
        $branches = Branch::active()
            ->activePopups()
            ->get(['id', 'name', 'type', 'start_date', 'end_date']);
        return response()->json($branches);
    }
    public function testGetOrders()
    {
        return self::getOrders();
    }

    public static function getOrders()
    {
        // جلب الطلبات الأساسية + العملاء + الفروع + مسؤولي المخازن
        $orders = DB::select("
        SELECT
            o.id, 
            o.type,
            o.active,
            o.description,
            o.customer_id,
            -- c.name AS customer_name,
            o.status,
            o.branch_id,
           --  b.name AS branch_name,
            o.notes, 
            o.total,
            o.created_at,
            o.updated_at
        FROM orders o
        -- LEFT JOIN users c ON c.id = o.customer_id
        -- LEFT JOIN branches b ON b.id = o.branch_id 
        ORDER BY o.created_at DESC
        LIMIT 80
    ");

     

        // تحويلها إلى Collection
        $orders = collect($orders)->map(function ($order) {
            // جلب تفاصيل الطلب بشكل منفصل لكل Order
        //     $details = DB::select("
        //     SELECT
        //         od.id,
        //         od.order_id,
        //         od.product_id,
        //         p.name AS product_name,
                
        //         cat.id AS product_category,
        //         od.unit_id,
        //         u.name AS unit_name,
        //         od.available_quantity AS quantity,
        //         od.available_quantity,
        //         od.price,
        //         od.available_in_store,
        //         cu.id AS created_by,
        //         cu.name AS created_by_user_name,
        //         od.is_created_due_to_qty_preivous_order,
        //         od.previous_order_id
        //     FROM orders_details od
        //     LEFT JOIN products p ON p.id = od.product_id
        //     LEFT JOIN categories cat ON cat.id = p.category_id
        //     LEFT JOIN units u ON u.id = od.unit_id
        //     LEFT JOIN users cu ON cu.id = od.created_by
        //     WHERE od.order_id = ?
        // ", [$order->id]);

            return [
                'id' => $order->id,
                'type' => $order->type,
                'active' => $order->active,
                'desc' => $order->description,
                'created_by' => $order->customer_id,
                'created_by_user_name' => 'شريف',
                'request_state_name' => $order->status,
                'branch_id' => $order->branch_id,
                'branch_name' => 'Test Branch',
                'notes' => $order->notes,
                'total_price' => $order->total,
                'created_at' => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($order->updated_at)->format('Y-m-d H:i:s'),
                'order_details' => [],
            ];
        });

        return response()->json($orders);
    }
}
