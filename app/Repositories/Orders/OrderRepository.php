<?php

namespace App\Repositories\Orders;

use App\Exports\OrdersExport;
use App\Http\Resources\OrderResource;
use App\Interfaces\Orders\OrderRepositoryInterface;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Store;
use App\Models\UnitPrice;
use App\Models\User;
use App\Services\FifoInventoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class OrderRepository implements OrderRepositoryInterface
{

    protected $model;

    public function __construct(Order $model)
    {
        $this->model = $model;
    }

    public function index($request)
    {
        $query = Order::query();


        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if (isBranchManager()) {
            if (auth()->user()->branch->is_central_kitchen && auth()->user()->branch->manager_abel_show_orders) {
                $query->whereIn('branch_id', DB::table('branches')
                    ->where('active', 1)->pluck('id')->toArray());
            } else {
                $query->where('branch_id', $request->user()->branch_id);
            }
        } else if (isBranchUser()) {
            $query->where('customer_id', auth()->user()->owner->id);
        }
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }

        if (isStoreManager()) {
            $query->where('status', '!=', Order::PENDING_APPROVAL);

            $centralKitchens = Store::whereIn('id', auth()->user()->managed_stores_ids)
                ->with('branches')
                ->where('is_central_kitchen', 1)->get();
            // $categories = $centralKitchens
            //     ->flatMap(function ($store) {
            //         return $store->branches->pluck('customized_manufacturing_categories');
            //     })
            //     ->filter() // ignore null or empty
            //     ->flatMap(function ($json) {
            //         return $json;
            //     })
            //     ->unique()
            //     ->values()
            //     ->map(fn($value) => (int) $value) // ensure integers
            //     ->toArray();


            if (is_array($centralKitchens->pluck('id')->toArray()) && count($centralKitchens->pluck('id')->toArray()) > 0) {

                $query->where('store_id', $centralKitchens->pluck('id')->toArray());
            } else {
                $query->where('store_id', null);
            }
        }
        $orders = $query->orderBy('created_at', 'DESC')->limit(80)->get();
        return OrderResource::collection($orders);
    }

    public function storeWithFifo($request)
    {
        try {
            DB::beginTransaction();



            if (isBranchManager()) {
                $customerId = auth()->user()->id;
            } else if (isBranchUser()) {
                $customerId = auth()->user()->owner->id;
            }

            $orderStatus = isBranchManager()  ? Order::ORDERED : Order::PENDING_APPROVAL;

            $allOrderDetails = $request->input('order_details');
            $notes = $request->input('notes');
            $description = $request->input('description');

            $allManufacturingBranches = Branch::active()
                ->where('is_central_kitchen', true)
                ->get(['id', 'customized_manufacturing_categories']);

            $manufacturedProductIds = [];

            foreach ($allManufacturingBranches as $branch) {
                $categories = $branch->customized_manufacturing_categories;

                if (is_array($categories) && count($categories)) {
                    $productsForThisBranch = collect($allOrderDetails)->filter(function ($item) use ($categories) {
                        $product = \App\Models\Product::find($item['product_id']);
                        return $product && in_array($product->category_id, $categories);
                    })->values()->all();

                    if (count($productsForThisBranch)) {
                        $manufacturingOrder = Order::create([
                            'status' => Order::ORDERED,
                            'customer_id' => $customerId,
                            'branch_id' => auth()->user()->branch->id,
                            'store_id' => $branch->store_id,
                            'type' => Order::TYPE_MANUFACTURING,
                            'notes' => $notes,
                            'description' => $description,
                        ]);

                        foreach ($productsForThisBranch as $productDetail) {
                            $fifoService = new FifoInventoryService(
                                $productDetail['product_id'],
                                $productDetail['unit_id'],
                                $productDetail['quantity']
                            );

                            $result = $fifoService->allocateOrder();
                            if ($result['success']) {
                                $data = $result['result'][0];
                                unset($data['movement_date'], $data['allocated_qty'], $data['unit_price']);
                                $data['order_id'] = $manufacturingOrder->id;
                                OrderDetails::create($data);
                                $manufacturedProductIds[] = $productDetail['product_id'];
                            } else {
                                throw new \Exception($result['message']);
                            }
                        }
                    }
                }
            }

            $normalOrderDetails = collect($allOrderDetails)->reject(function ($item) use ($manufacturedProductIds) {
                return in_array($item['product_id'], $manufacturedProductIds);
            })->values()->all();

            $order = Order::create([
                'status' => $orderStatus,
                'customer_id' => $customerId,
                'branch_id' => auth()->user()?->branch?->id,
                'type' => Order::TYPE_NORMAL,
                'notes' => $notes,
                'description' => $description,
            ]);

            $orderId = $order->id;

            foreach ($normalOrderDetails as $key => $detail) {
                $fifoService = new FifoInventoryService($detail['product_id'], $detail['unit_id'], $detail['quantity']);
                $result = $fifoService->allocateOrder();

                if ($result['success']) {
                    $data = $result['result'][0];
                    unset($data['movement_date'], $data['allocated_qty'], $data['unit_price']);
                    $data['order_id'] = $orderId;
                    OrderDetails::create($data);
                } else {
                    throw new \Exception($result['message']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orders created successfully',
                'order' => $order,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function storeWithFifo_old($request)
    {
        try {
            DB::beginTransaction();
            // to get current user role
            $currnetRole = getCurrentRole();

            if ($currnetRole == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'you dont have any role'
                ], 500);
            }


            $pendingOrderId = 0;
            $message = '';
            // check if user has pending for approval order to determine branchId & orderId & orderStatus
            if (isBranchManager()) { // Role 7 is Branch
                $branchId = auth()->user()?->branch?->id;
                $customerId = auth()->user()->id;
                if (!isset($branchId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not manager of any branch'
                    ], 500);
                }
                $orderStatus = Order::ORDERED;
            } else if (isBranchUser()) { // Role 8 is User
                $orderStatus = Order::PENDING_APPROVAL;
                $branchId = auth()->user()->owner->branch->id;
                $customerId = auth()->user()->owner->id;
            }
            $pendingOrderId  =    checkIfUserHasPendingForApprovalOrder($branchId);

            // Map order data from request body 
            $orderData = [
                'status' => $orderStatus,
                'customer_id' => $customerId,
                'branch_id' => $branchId,
                'notes' => $request->input('notes'),
                'description' => $request->input('description'),
            ];
            if ($request->input('order_type') && $request->input('order_type') == Order::TYPE_MANUFACTURING) {
                // $orderData['type'] = Order::TYPE_MANUFACTURING;
            }

            // Create new order
            if (!($pendingOrderId > 0)) {
                $order = Order::create($orderData);
                $orderId = $order->id;
                $message = 'done successfully';
            } else if ($pendingOrderId > 0) {
                $orderDetailsData = calculateFifoMethod($request->input('order_details'), $pendingOrderId);
                handlePendingOrderDetails($orderDetailsData);
                $orderId = $pendingOrderId;
                if ($currnetRole == 8) {
                    Order::find($orderId)->update([
                        'updated_by' => auth()->user()->id,
                    ]);
                    $message = 'Your order has been submited on pending approval order no ' . $orderId;
                } else if ($currnetRole == 7) {
                    Order::find($orderId)->update([
                        'updated_by' => auth()->user()->id,
                        'status' => Order::ORDERED,
                    ]);
                    $message = 'done successfully';
                }
            }
            $orderDetailsData = [];
            foreach ($request->input('order_details') as $key =>  $detail) {

                $fifoService = new FifoInventoryService($detail['product_id'], $detail['unit_id'], $detail['quantity']);
                $result =  $fifoService->allocateOrder();

                if ($result['success'] == true) {
                    $orderDetailsData[] = $result['result'][0];
                    $orderDetailsData[$key]['order_id'] = $orderId;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                    ], 500);
                }
            }

            // $orderDetailsData_old = calculateFifoMethod($request->input('order_details'), $orderId);
            // dd($orderDetailsData);
            $orderDetailsData = array_map(function ($item) {
                unset(
                    $item['movement_date'],
                    $item['allocated_qty'],
                    $item['unit_price'],
                );
                return $item;
            }, $orderDetailsData);

            // dd($orderDetailsData);
            // if (count($orderDetailsData) > 0 && !($pendingOrderId > 0)) {
            if (count($orderDetailsData) > 0) {
                // to store (order details) new order 
                OrderDetails::insert($orderDetailsData);
            }

            //to calculate the total of order when store it
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'order' => Order::find($orderId),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function storeWithUnitPricing($request)
    {
        try {
            DB::beginTransaction();
            // to get current user role
            $currnetRole = getCurrentRole();
            if (!isset($currnetRole)) {
                return response()->json([
                    'success' => false,
                    'message' => 'you dont have any role',
                ], 500);
            }
            $pendingOrderId = 0;
            $message = '';
            // check if user has pending for approval order to determine branchId & orderId & orderStatus
            if ($currnetRole == 7) {
                $branchId = auth()->user()?->branch?->id;
                $customerId = auth()->user()->id;
                if (!isset($branchId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not manager of any branch',
                    ], 500);
                }
                $orderStatus = Order::ORDERED;
            } else if ($currnetRole == 8) {
                $orderStatus = Order::PENDING_APPROVAL;
                $branchId = auth()->user()->owner->branch->id;
                $customerId = auth()->user()->owner->id;
            }
            $pendingOrderId  = checkIfUserHasPendingForApprovalOrder($branchId);

            // Map order data from request body
            $orderData = [
                'status' => $orderStatus,
                'customer_id' => $customerId,
                'branch_id' => $branchId,
                'notes' => $request->input('notes'),
                'description' => $request->input('description'),
            ];

            // Create new order
            if ($pendingOrderId > 0) {
                $orderId = $pendingOrderId;
                Order::find($orderId)->update([
                    'updated_by' => auth()->user()->id,
                ]);
                $message = 'Your order has been submited on pending approval order no ' . $orderId;
            } else {
                $order = Order::create($orderData);
                $orderId = $order->id;
                $message = 'done successfully';
            }

            // Map order details data from request body
            $orderDetailsData = [];
            foreach ($request->input('order_details') as $orderDetail) {

                if ($pendingOrderId) {
                    $existOrderDetail = OrderDetails::where(
                        'order_id',
                        $pendingOrderId
                    )->where(
                        'product_id',
                        $orderDetail['product_id']
                    )->where(
                        'unit_id',
                        $orderDetail['unit_id']
                    )->first();
                    if ($existOrderDetail) {
                        $newQuantity = $existOrderDetail->quantity + $orderDetail['quantity'];
                        $existOrderDetail->update([
                            'updated_by' => auth()->user()->id,
                            'quantity' => $newQuantity,
                            'available_quantity' => $newQuantity,
                            'price' =>
                            getUnitPrice($orderDetail['product_id'], $orderDetail['unit_id']),
                        ]);
                        continue;
                    }
                }
                $orderDetailsData[] = [
                    'order_id' => $orderId,
                    'product_id' => $orderDetail['product_id'],
                    'unit_id' => $orderDetail['unit_id'],
                    'orderd_product_id' => $orderDetail['product_id'],
                    'ordered_unit_id' => $orderDetail['unit_id'],
                    'quantity' => $orderDetail['quantity'],
                    'available_quantity' => $orderDetail['quantity'],
                    'created_by' => auth()->user()->id,
                    'price' => (getUnitPrice($orderDetail['product_id'], $orderDetail['unit_id'])),
                    'package_size' => UnitPrice::where('product_id', $orderDetail['product_id'])
                        ->where('unit_id', $orderDetail['unit_id'])->value('package_size'),
                ];
            }
            if (count($orderDetailsData) > 0) {
                OrderDetails::insert($orderDetailsData);
            }

            //to calculate the total of order when store it
            $totalPrice = array_reduce($orderDetailsData, function ($carry, $item) {
                return $carry + $item['price'];
            }, 0);
            Order::find($orderId)->update(['total' => $totalPrice]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                // 'order' => $order->where('id',$orderId)->with('orderDetails')->get(),
                'order' => Order::find($orderId),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function update($request, $id)
    {



        try {
            // Start a database transaction
            DB::beginTransaction();

            if (auth()->user()->managedStores->count() == 0 && isStoreManager()) {
                return response()->json([
                    'success' => false,
                    'orderId' => null,
                    'message' => "You Are not a Store Keeper",
                ], 500);
            }
            // $branchId = auth()->user()->managedStores->first()->id;
            try {
                // Find the order by the given ID or throw a ModelNotFoundException
                $order = Order::findOrFail($id);
            } catch (ModelNotFoundException $e) {
                // Roll back the transaction and return an error response if the order is not found
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'orderId' => null,
                    'message' => "Order not found with $id id",
                ], 404);
            }

            // Validate the request data
            $validatedData = $request->validate([
                'status' => [
                    'string',
                    Rule::in([
                        Order::PROCESSING,
                        Order::READY_FOR_DELEVIRY,
                        Order::DELEVIRED,
                        Order::ORDERED,
                    ]),
                ],
                'notes' => 'string',
                'full_quantity' => 'boolean',
                'active' => 'boolean',
            ]);
            $order->updated_by = auth()->user()->id;
            // Fill the order with the validated data and save it to the database

            if (in_array($request->status, [Order::READY_FOR_DELEVIRY])) {
                $order->update([
                    'transfer_date' => now(),
                ]);
            }
            $order->fill($validatedData)->save();

            // Commit the transaction
            DB::commit();

            // Return a success response with the updated order information
            return [
                'success' => true,
                'orderId' => $order->id,
                'message' => 'done successfully',
            ];
        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            DB::rollBack();

            // Handle the exception and return an error response
            return response()->json([
                'success' => false,
                'orderId' => $order->id,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function export($id)
    {
        $order = Order::find($id);
        $order_branch = Branch::find($order->branch_id)->name;
        $order_status = $order->status;
        $file_name = __('lang.order-no-') . $id;
        if (in_array($order_status, [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])) {
            $file_name = __('lang.transfer-no-') . $id . ' - ' . $order->transfer_date;
        }
        return Excel::download(new OrdersExport($id), $order_branch . ' - ' . $file_name . '.xlsx');
    }
    public function exportTransfer($id)
    {
        return Excel::download(new OrdersExport($id), 'transfer-no-' . $id . '.xlsx');
    }
}
