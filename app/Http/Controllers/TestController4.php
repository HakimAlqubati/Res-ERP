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
                        $branchIdsStr = implode(',', $branchIds);
                        $where[] = "(o.branch_id IN ($branchIdsStr) OR o.branch_id = {$user->branch->id})";
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
            if ($user->branch?->is_central_kitchen && count($customCategories)) {
                // Can't filter by category easily in raw SQL, handled in Eloquent, you may ignore here or redesign this logic
                // You can later filter on front-end if needed
            }
        }

        // 🧠 Assemble WHERE clause
        $whereSql = implode(' AND ', $where);

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

        // 🧩 Transform result
        $orders = collect($orders)->map(function ($order) use ($branches, $customers) {
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
        $details = DB::select("
        SELECT
            od.id,
            od.order_id,
            od.product_id,
           
            od.unit_id,
            u.name AS unit_name,
            od.available_quantity AS quantity,
            od.available_quantity,
            od.price,
            od.available_in_store,
            cu.id AS created_by,
            cu.name AS created_by_user_name,
            od.is_created_due_to_qty_preivous_order,
            od.previous_order_id
        FROM orders_details od
         
        LEFT JOIN units u ON u.id = od.unit_id
        LEFT JOIN users cu ON cu.id = od.created_by
        WHERE od.order_id = ?
    ", [$id]);

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
        $details = collect($details)->map(function ($detail) use ($products, $units) {
            return [
                'id' => $detail->id,
                'order_id' => $detail->order_id,
                'product_id' => $detail->product_id,
                'product_name' => $products[$detail->product_id]->name ?? null,
                'product_category' => $products[$detail->product_id]->category_id ?? null,
                // 'unit_prices' => $products[$detail->product_id]->unitPrices ?? null,
                'unit_id' => $detail->unit_id,
                'unit_name' => $units[$detail->unit_id]->name ?? null,
                'quantity' => $detail->quantity,
                'available_quantity' => $detail->available_quantity,
                'price' => $detail->price,
                'available_in_store' => $detail->available_in_store,
                // 'created_by' => $detail->created_by,
                // 'created_by_user_name' => $detail->created_by_user_name,
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
}
