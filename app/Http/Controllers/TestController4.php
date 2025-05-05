<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Services\FifoInventoryService;
use App\Services\Firebase\FcmClient;
use App\Services\InventoryService;
use App\Services\MultiProductsInventoryService;
use App\Services\Orders\Reports\OrdersReportsService;
use App\Services\Orders\Reports\ReorderDueToStockReportService;
use App\Services\StockInventoryReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController4 extends Controller
{

    public function testGetOrders()
    {
        $request = request();
        return self::getOrders($request);
    }

    public static function getOrders($request)
    {
        $page = max((int) $request->input('page', 1), 1);
        $perPage = (int) $request->input('per_page', 20);
        $offset = ($page - 1) * $perPage;

        $where = ['1=1']; // default

        // ✅ Basic filters
        if ($request->has('customer_id')) {
            $where[] = 'o.customer_id = ' . (int) $request->customer_id;
        }


        if ($request->has('id')) {
            $where[] = 'o.id = ' . (int) $request->id;
        }

        // ✅ Role-based filters
        $user = auth()->user();

        if (isBranchUser()) {
            $where[] = 'o.customer_id = ' . (int) $user->owner->id;
        }

        if (isDriver()) {
            $statuses = [
                "'" . Order::READY_FOR_DELEVIRY . "'",
                "'" . Order::DELEVIRED . "'"
            ];
            $where[] = 'o.status IN (' . implode(',', $statuses) . ')';
        }

        if (isBranchManager()) {
            if (!isStoreManager() && $user->branch->is_kitchen) {
                if ($user->branch->manager_abel_show_orders) {
                    $branchIds = DB::table('branches')
                        ->where('active', 1)
                        ->where('id', '!=', $user->branch->id)
                        ->pluck('id')->toArray();

                    if (count($branchIds)) {
                        $otherBranchesCategories = \App\Models\Branch::centralKitchens()
                            ->where('id', '!=', auth()->user()?->branch?->id)
                            ->with('categories:id')
                            ->get()
                            ->pluck('categories')
                            ->flatten()
                            ->pluck('id')
                            ->unique()
                            ->toArray();
                        $otherBranchesCategoriesStr = implode(',', $otherBranchesCategories);
                        $branchIdsStr = implode(',', $branchIds);
                        $where[] = "(o.branch_id IN ($branchIdsStr) OR o.branch_id = {$user->branch->id})";
                        $where[] = "EXISTS (
                            SELECT 1
                            FROM orders_details od
                            JOIN products p ON od.product_id = p.id
                            JOIN categories c ON p.category_id = c.id
                            WHERE od.order_id = o.id AND c.is_manafacturing = 1 and c.id NOT IN ($otherBranchesCategoriesStr)
                        ) OR o.customer_id = {$user->id}";
                    } else {
                        $where[] = "o.branch_id = {$user->branch->id}";
                    }
                } else {
                    $where[] = "o.branch_id = {$user->branch->id}";
                }
            } elseif (!$user->branch->is_kitchen) {
                $where[] = "o.branch_id = {$user->branch->id}";
            }
        }

        if (isStoreManager()) {

            $where[] = "o.status != '" . Order::PENDING_APPROVAL . "'";
            $customCategories = $user->branch?->categories()->pluck('category_id')->toArray() ?? [];
            if (isBranchManager() && $user->branch?->is_central_kitchen && count($customCategories)) {
                // Can't filter by category easily in raw SQL, handled in Eloquent, you may ignore here or redesign this logic
                // You can later filter on front-end if needed
                $categoryIds = implode(',', $customCategories);

                $where[] = "EXISTS (
                    SELECT 1
                    FROM orders_details od
                    JOIN products p ON od.product_id = p.id
                    JOIN categories c ON p.category_id = c.id
                    WHERE od.order_id = o.id AND c.id IN ($categoryIds)
                ) OR o.customer_id = {$user->id}";
            } else {
                $allCustomizedCategories = \App\Models\Branch::centralKitchens()
                    ->with('categories:id')
                    ->get()
                    ->pluck('categories')
                    ->flatten()
                    ->pluck('id')
                    ->unique()
                    ->toArray();
                if (count($allCustomizedCategories)) {
                    $allCustomizedCategoriesStr = implode(',', $allCustomizedCategories);
                    $where[] = "EXISTS (
                        SELECT 1
                        FROM orders_details od
                        JOIN products p ON od.product_id = p.id
                        JOIN categories c ON p.category_id = c.id
                        WHERE od.order_id = o.id AND c.id NOT IN  ($allCustomizedCategoriesStr)
                    ) OR o.customer_id = {$user->id}";
                }
            }
        }

        // 🧠 Assemble WHERE clause
        $whereSql = implode(' AND ', $where);
        // dd($whereSql,$user->branch?->is_central_kitchen);
        // 📊 Get total count
        $total = DB::table('orders as o')
            ->whereRaw($whereSql)
            ->count();

        // 📦 Fetch paginated data
        $orders = DB::select("
        SELECT
            o.id, 
            o.active, 
            o.customer_id,
            o.status,
            o.branch_id,
            o.created_at,
            o.updated_at
        FROM orders o
        WHERE $whereSql
        ORDER BY o.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");

        // 📚 Load related data
        $customerIds = collect($orders)->pluck('customer_id')->unique()->toArray();
        $customers = DB::table('users')->whereIn('id', $customerIds)
            ->get(['id', 'name'])->keyBy('id');

        $branches = DB::table('branches')->where('active', 1)
            ->get(['id', 'name'])->keyBy('id');
        $orderIds = collect($orders)->pluck('id')->unique()->toArray();
        $ordersWithPreviousQty = DB::table('orders_details')
            ->whereIn('order_id', $orderIds)
            ->where('is_created_due_to_qty_preivous_order', 1)
            ->pluck('order_id')
            ->unique()
            ->flip();
        // 🧩 Transform result
        $orders = collect($orders)->map(function ($order) use ($branches, $customers, $ordersWithPreviousQty) {

            return [
                'id' => $order->id,
                'active' => $order->active,
                'created_by' => $order->customer_id,
                'created_by_user_name' => $customers[$order->customer_id]->name ?? null,
                'request_state_name' => $order->status,
                'branch_id' => $order->branch_id,
                'branch_name' => $branches[$order->branch_id]->name ?? null,
                'total_price' => 0,
                'created_at' => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($order->updated_at)->format('Y-m-d H:i:s'),
                'has_created_due_to_previous_order' => isset($ordersWithPreviousQty[$order->id]),

            ];
        });

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'data' => $orders,
        ]);
    }

    public function testGetOrdersDetails($id)
    {


        $user = auth()->user();

        // Check if branch is kitchen
        // if ($user->branch->is_kitchen) {
        //     // First, check if the order contains any manufacturing category product
        //     $check = DB::selectOne("
        //         SELECT 1
        //         FROM orders_details od
        //         JOIN products p ON od.product_id = p.id
        //         JOIN categories c ON p.category_id = c.id
        //         WHERE od.order_id = ? AND c.is_manafacturing = 1
        //         LIMIT 1
        //     ", [$id]);

        //     // If not manufacturing-related, return empty
        //     if (!$check) {
        //         return response()->json([]); // Or customize message
        //     }
        // }

        $query = "
        SELECT
            od.id,
            od.order_id,
            od.product_id,
           
            od.unit_id,
           
            od.available_quantity AS quantity,
            od.available_quantity,
            od.price,
            od.available_in_store,
           
            od.is_created_due_to_qty_preivous_order,
            od.previous_order_id
        FROM orders_details od ";
        if (isBranchManager() &&  $user->branch->is_kitchen) {

            if (!isStoreManager()) {
                $query .= "JOIN products p ON od.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            JOIN orders o ON od.order_id = o.id
            
AND (
    (o.customer_id != {$user->id} AND c.is_manafacturing = 1)
    OR
    (o.customer_id = {$user->id} AND (c.is_manafacturing = 1 OR c.is_manafacturing = 0))
)
";

                $otherBranchesCategories = \App\Models\Branch::centralKitchens()
                    ->where('id', '!=', auth()->user()?->branch?->id)
                    ->with('categories:id')
                    ->get()
                    ->pluck('categories')
                    ->flatten()
                    ->pluck('id')
                    ->unique()
                    ->toArray();
                if (count($otherBranchesCategories)) {
                    $otherBranchesCategoriesStr = implode(',', $otherBranchesCategories);
                    $query .= " and c.id NOT IN ($otherBranchesCategoriesStr) ";
                }
            } else {
                $customCategories = $user->branch?->categories()->pluck('category_id')->toArray() ?? [];
                $query .= "JOIN products p ON od.product_id = p.id
                JOIN categories c ON p.category_id = c.id 
                JOIN orders o ON od.order_id = o.id
                ";

                if (count($customCategories) > 0) {
                    $categoryIds = implode(',', $customCategories);
                    // $query .= " and c.id IN ($categoryIds) ";
                }
                $query .= " AND (
                     (o.customer_id != {$user->id} AND c.is_manafacturing = 1 and c.id IN ($categoryIds)) 
                    OR
                    (o.customer_id = {$user->id} AND (c.is_manafacturing = 1 OR c.is_manafacturing = 0))
                     )
                 ";
            }
        }
        if (isStoreManager() && !isBranchManager()) {
            $allCustomizedCategories = \App\Models\Branch::centralKitchens()
                ->with('categories:id')
                ->get()
                ->pluck('categories')
                ->flatten()
                ->pluck('id')
                ->unique()
                ->toArray();
            if (count($allCustomizedCategories)) {
                $allCustomizedCategoriesStr = implode(',', $allCustomizedCategories);
                $query .= "
                JOIN products p ON od.product_id = p.id
                JOIN categories c ON p.category_id = c.id 
                JOIN orders o ON od.order_id = o.id
                and c.id NOT IN ($allCustomizedCategoriesStr) ";
            }
        }
        $query .=  "WHERE od.order_id = ?";

        $details = DB::select($query, [$id]);

        // if(auth()->user()->branch->is_kitchen){
        // $where[] = "EXISTS (
        //     SELECT 1
        //     FROM orders_details od
        //     JOIN products p ON od.product_id = p.id
        //     JOIN categories c ON p.category_id = c.id
        //     WHERE od.order_id = o.id AND c.is_manafacturing = 1
        // ) OR o.customer_id = {$user->id}";
        // }
        $productIds = collect($details)->pluck('product_id')->unique()->toArray();
        $products = DB::table('products')->whereIn('id', $productIds)
            ->get([
                'id',
                'name',
                'category_id'
            ])->keyBy('id');

        $unitIds = collect($details)->pluck('unit_id')->unique()->toArray();
        $units = DB::table('units')->whereIn('id', $unitIds)
            ->get([
                'id',
                'name'
            ])->keyBy('id');
        $service = new MultiProductsInventoryService();
        $details = collect($details)->map(function ($detail) use ($products, $units, $service) {
            return [
                'id' => $detail->id,
                'order_id' => $detail->order_id,
                'product_id' => $detail->product_id,
                'product_name' => $products[$detail->product_id]->name ?? null,
                'product_category' => $products[$detail->product_id]->category_id ?? null,
                'unit_prices' => $service->getProductUnitPrices($detail->product_id),
                'unit_id' => $detail->unit_id,
                'unit_name' => $units[$detail->unit_id]->name ?? null,
                'quantity' => $detail->quantity,
                'available_quantity' => $detail->available_quantity,
                'price' => $detail->price,
                'available_in_store' => $detail->available_in_store,
                'is_created_due_to_qty_preivous_order' => $detail->is_created_due_to_qty_preivous_order,
                'previous_order_id' => $detail->previous_order_id
            ];
        });
        return response()->json($details);
    }





    public static function getOrders_beforeConditions($request)
    {
        // 📌 Pagination inputs
        $page = max((int) $request->input('page', 1), 1);
        $perPage = (int) $request->input('per_page', 20);
        $offset = ($page - 1) * $perPage;

        $total = DB::table('orders')->count();
        $orders = DB::select("
        SELECT
            o.id, 
            o.active, 
            o.customer_id,
            o.status,
            o.branch_id,
            o.created_at,
            o.updated_at
        FROM orders o
        ORDER BY o.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");

        $customerIds = collect($orders)->pluck('customer_id')->unique()->toArray();
        $customers = DB::table('users')->whereIn('id', $customerIds)
            ->get(['id', 'name'])->keyBy('id');
        $branches =
            DB::table('branches')->where('active', 1)
            ->get(['id', 'name'])->keyBy('id');

        $orders = collect($orders)->map(function ($order) use ($branches, $customers) {
            return [
                'id' => $order->id,
                'active' => $order->active,

                'created_by' => $order->customer_id,
                'created_by_user_name' => $customers[$order->customer_id]->name ?? null,
                'request_state_name' => $order->status,
                'branch_id' => $order->branch_id,
                'branch_name' => $branches[$order->branch_id]->name,
                'total_price' => 00,
                'created_at' => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($order->updated_at)->format('Y-m-d H:i:s'),
            ];
        });

        // 🔙 Return paginated response
        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'data' => $orders,
        ]);
    }


    public function generatePendingApprovalPreviousOrderDetailsReport(Request $request)
    {
        $groupByOrder = $request->boolean('group_by_order', false);
        $result =  (new ReorderDueToStockReportService())->getReorderDueToStockReport($groupByOrder);
        return response()->json([$result, count($result)]);
    }

    public function missingProducts(Request $request)
    {


        $products = StockInventoryReportService::getProductsNotInventoriedBetween(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'status' => 'success',
            'data' => $products,
            'count' => $products->count(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
    }

    public function branchConsumptionReport(Request $request)
    {
        $intervalType = $request->input('interval_type', OrdersReportsService::INTERVAL_DAILY);

        if (!in_array($intervalType, OrdersReportsService::INTERVALS)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid interval_type value. Allowed: daily, weekly, monthly.',
            ], 422);
        }
        $fromDate = $request->input('from_date', now()->subDays(7)->format('Y-m-d'));
        $toDate = $request->input('to_date', now()->format('Y-m-d'));
        $branchIds = $request->input('branch_ids');      // array
        $productIds = $request->input('product_ids');    // array
        $categoryIds = $request->input('category_ids');
        if ($productIds && !is_array($productIds)) {
            $productIds = explode(',', $productIds);
        }
        if ($branchIds && !is_array($branchIds)) {
            $branchIds = explode(',', $branchIds);
        }
        if ($categoryIds && !is_array($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }

        $data = OrdersReportsService::getBranchConsumption(
            $fromDate,
            $toDate,
            $branchIds,
            $productIds,
            $categoryIds,
        );

        return view('reports.branch-consumption', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'intervalType' => $intervalType,
            'data' => $data,
        ]);
    }
}
