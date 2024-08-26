<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailsController;
use App\Http\Controllers\ProductController;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/compare', function (Request $request) {
    $qty = $request->input('qty');
    $product_id = $request->input('product_id');
    $unit_id = $request->input('unit_id');
    // $fdata = getSumQtyOfProductFromPurchases($product_id, $unit_id);
    $fdata = comparePurchasedWithOrderdQties($product_id, $unit_id);

    return $fdata;
});
Route::get('/to_try_order', function (Request $request) {
    $req_array = $request->all();
    $fdata = [];
    $fdata  = calculateFifoMethod($req_array['order_details'], 15);

    return $fdata;
});

Route::post('/login', [AuthController::class, 'login']);
Route::get('/products', [ProductController::class, 'index']);
Route::middleware('auth:api')->group(function () {
    Route::get('/report_products', [ProductController::class, 'reportProducts']);
    Route::get('/user', [AuthController::class, 'getCurrnetUser']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('orders', OrderController::class);
    Route::resource('orderDetails', OrderDetailsController::class);
    Route::patch('patch', [OrderDetailsController::class, 'update']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/v2/report_products', [ProductController::class, 'reportProductsv2']);
    Route::get('/v2/report_products/details/{category_id}', [ProductController::class, 'reportProductsv2Details']);
    Route::get('/getProductOrderQuantities', [ProductController::class, 'getProductOrderQuantities']);
});

Route::get('/test', function () {
    return User::role([1, 3])->pluck('id');
});

Route::get('/new-link', function () {
});

Route::get('/branches', function () {
    return Branch::get(['id', 'name']);
});










/*************************************** */
// Add Users 
// Get the list of users from the second database
function addUsers()
{
    $users = DB::connection('second')->table('users')->get();

    // Loop through each user and insert them into the normal database
    foreach ($users as $user) {
        // Insert the user into the normal database
        DB::table('users')->insert([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => $user->password,
            'owner_id' => $user->owner_id,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);

        switch ($user->role_id) {
            case 3:
                $newRoleId = 7;
                break;
            case 4:
                $newRoleId = 3;
                break;
            case 5:
                $newRoleId = 9;
                break;
            case 6:
                $newRoleId = 6;
                break;
            case 7:
                $newRoleId = 5;
                break;
            case 8:
                $newRoleId = 8;
                break;
            case 10:
                $newRoleId = 10;
                break;
            default:
                $newRoleId = null;
                break;
        }

        if (is_numeric($newRoleId)) {
            // Insert the model_has_roles record into the normal database
            DB::table('model_has_roles')->insert([
                'role_id' => $newRoleId,
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
            ]);
        }
    }
}

/**************************************************************/
// Add order details
function addOrderDetails()
{
    // Get the list of order details from the second database
    $orderDetails = DB::connection('second')
        ->table('order_details')
        ->whereIn('order_id', [4039, 4040])
        ->whereNotNull('product_id')
        ->get();

    // Loop through each order detail and insert it into the normal database
    foreach ($orderDetails as $orderDetail) {
        DB::table('orders_details')->insert([
            'order_id' => $orderDetail->order_id,
            'product_id' => $orderDetail->product_id,
            'unit_id' => $orderDetail->product_unit_id,
            'quantity' => $orderDetail->qty,
            'available_quantity' => $orderDetail->available_qty,
            'price' => $orderDetail->price,
            'available_in_store' => $orderDetail->available_in_store,
            'created_by' => $orderDetail->created_by,
            'created_at' => $orderDetail->created_at,
            'updated_at' => $orderDetail->updated_at,
        ]);
    }
}
//*********************************************************** */
// Add Orders

function addOrders()
{
    $orders = DB::connection('second')
        ->table('orders')
        ->get();
    foreach ($orders as $order) {
        DB::table('orders')
            ->insert([
                'id' => $order->id,
                'customer_id' => $order->created_by,
                'status' => getStatus($order->request_state_id),
                'recorded' => getRecording($order->restricted_state_id),
                'notes' => $order->notes,
                'description' => $order->desc,
                'full_quantity' => $order->full_quantity,
                'branch_id' => $order->branch_id,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at
            ]);
    }
}
function getStatus($requestStateId)
{
    if ($requestStateId == 2) {
        return Order::ORDERED;
    } elseif ($requestStateId == 3) {
        return Order::PROCESSING;
    } elseif ($requestStateId == 4) {
        return Order::READY_FOR_DELEVIRY;
    } elseif ($requestStateId == 5) {
        return Order::DELEVIRED;
    } elseif ($requestStateId == 8) {
        return Order::PENDING_APPROVAL;
    }
}

function getRecording($restrictedStateId)
{
    if ($restrictedStateId == 6) {
        return 0;
    } elseif ($restrictedStateId == 7) {
        return 1;
    }
}
