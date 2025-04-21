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
use Illuminate\Support\Facades\Validator;
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


        $query->whereHas('orderDetails');
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $otherBranchesCategories = \App\Models\Branch::centralKitchens()
            ->where('id', '!=', auth()->user()?->branch?->id) // نستثني فرع المستخدم
            ->with('categories:id')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();
        if (isBranchManager()) {

            if (!isStoreManager() && auth()->user()->branch->is_kitchen) {
                if (auth()->user()->branch->manager_abel_show_orders) {
                    $query
                        ->whereIn('branch_id', DB::table('branches')
                            ->where('active', 1)
                            ->whereNot('id', auth()->user()->branch->id)
                            ->pluck('id')->toArray())
                        ->whereHas('orderDetails.product.category', function ($q) use ($otherBranchesCategories) {
                            $q->where('is_manafacturing', true)
                                ->whereNotIn('categories.id', $otherBranchesCategories);
                        })
                        ->orWhere('branch_id', auth()->user()->branch->id)
                    ;
                } else {
                    $query
                        ->where('branch_id', auth()->user()->branch->id);
                }
            } elseif (!auth()->user()->branch->is_kitchen) {
                $query->where('branch_id', $request->user()->branch_id);
            }
        }
        if (!isStoreManager() && auth()->user()->branch->is_kitchen && auth()->user()->branch->manager_abel_show_orders) {
            $query
                ->whereIn('branch_id', DB::table('branches')
                    ->where('active', 1)
                    ->whereNot('id', auth()->user()->branch->id)
                    ->pluck('id')->toArray())
                ->with(['orderDetails' => function ($q) use ($otherBranchesCategories) {
                    $q->where(function ($qDetail) use ($otherBranchesCategories) {
                        $qDetail->whereHas('order', function ($qOrder) {
                            $qOrder->where('branch_id', '!=', auth()->user()->branch->id);
                        })->whereHas('product.category', function ($q2) use ($otherBranchesCategories) {
                            $q2->where('is_manafacturing', true)
                                ->whereNotIn('categories.id', $otherBranchesCategories);
                        });
                    })
                        ->orWhereHas('order', function ($qOrder) {
                            // 🟢 هذا هو الطلب من نفس فرع المستخدم → لا فلترة
                            $qOrder->where('branch_id', auth()->user()->branch->id);
                        });
                }])
                ->orWhere('branch_id', auth()->user()->branch->id)
            ;
        }

        if (isBranchUser()) {
            $query->where('customer_id', auth()->user()->owner->id);
        }
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }

        if (isStoreManager()) {

            $query->where('status', '!=', Order::PENDING_APPROVAL);

            $customCategories = auth()->user()?->branch?->categories()->pluck('category_id')->toArray() ?? [];
            if (auth()->user()->branch?->is_central_kitchen && count($customCategories) > 0) {
                $query->whereHas('orderDetails.product.category', function ($q) use ($customCategories) {
                    $q->whereIn('categories.id', $customCategories);
                })
                    // ->orWhere('customer_id', auth()->user()->id)
                ;
            }
        }
        if (isDriver()) {
            $query->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED]);
        }
        // $query->where('branch_id', '!=', auth()->user()->branch_id);
        $orders = $query->orderBy('created_at', 'DESC')->limit(80)
            ->get();

        return OrderResource::collection($orders)->filter();
    }

    public function storeWithFifo($request)
    {
        $validator = Validator::make($request->all(), [
            'order_details' => 'required|array|min:1',
            'order_details.*.quantity' => 'required|numeric|min:0.1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'errors' => $validator->errors(),
            ], 422);
        }
        try {
            DB::beginTransaction();

            $branchId = auth()->user()->branch?->id;
            $customerId = isBranchManager()
                ? auth()->user()->id
                : (isBranchUser() ? auth()->user()->owner->id : null);
            $pendingOrderId = checkIfUserHasPendingForApprovalOrder($branchId);

            $orderStatus = isBranchManager() ? Order::ORDERED : Order::PENDING_APPROVAL;



            $allOrderDetails = $request->input('order_details');
            $notes = $request->input('notes');
            $description = $request->input('description');

            // 👇 تحديد الفئات الخاصة بالتصنيع
            $manufacturingCategoryIds = \App\Models\Category::Manufacturing()->pluck('id')->toArray();

            // // 👇 إذا الفرع الحالي هو مطبخ مركزي
            // if (auth()->user()?->branch?->is_kitchen) {
            //     foreach ($allOrderDetails as $item) {
            //         $product = \App\Models\Product::find($item['product_id']);
            //         if ($product && in_array($product->category_id, $manufacturingCategoryIds)) {
            //             // throw new \Exception("Central kitchens are not allowed to create orders that contain manufacturing products such as ({$product->name}-{$product->id}).");
            //         }
            //     }
            // }

            $allManufacturingBranches = Branch::active()
                ->centralKitchens()
                ->with('categories:id')
                ->get(['id', 'store_id']);

            $manufacturedProductIds = [];
            foreach ($allManufacturingBranches as $branch) {
                $categories = $branch->categories->pluck('id')->toArray();

                $productsForThisBranch = collect($allOrderDetails)->filter(function ($item) use ($categories, $branch) {
                    $product = \App\Models\Product::find($item['product_id']);
                    $isForbidden = auth()->check() &&
                        auth()->user()->branch_id === $branch->id &&
                        in_array($product->category_id, $categories);
                    if ($isForbidden) {
                        throw new \Exception("You cannot request the product ({$product->name}-{$product->id}) because it belongs to a manufacturing category assigned to your own branch.");
                    }
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
                        $productDetail['price'] = getUnitPrice($productDetail['product_id'], $productDetail['unit_id']);
                        $productDetail['order_id'] = $manufacturingOrder->id;
                        OrderDetails::create($productDetail);
                        $manufacturedProductIds[] = $productDetail['product_id'];
                    }
                }
            }

            $normalOrderDetails = collect($allOrderDetails)->reject(function ($item) use ($manufacturedProductIds) {
                return in_array($item['product_id'], $manufacturedProductIds);
            })->values()->all();
            if ($pendingOrderId > 0) {
                $order = Order::find($pendingOrderId);
                $order->update(['updated_by' => auth()->id()]);
                $orderId = $pendingOrderId;
                $orderStatus = $order->status; // قد يكون PENDING_APPROVAL أو ORDERED
            } else {
                $order = Order::create([
                    'status' => $orderStatus,
                    'customer_id' => $customerId,
                    'branch_id' => auth()->user()?->branch?->id,
                    'type' => Order::TYPE_NORMAL,
                    'notes' => $notes,
                    'description' => $description,
                    'store_id' =>  auth()->user()?->branch?->valid_store_id,
                ]);

                $orderId = $order->id;
            }
            foreach ($normalOrderDetails as $detail) {
                $existingDetail = OrderDetails::where([
                    ['order_id', '=', $orderId],
                    ['product_id', '=', $detail['product_id']],
                    ['unit_id', '=', $detail['unit_id']],
                ])->first();

                if ($existingDetail) {
                    // تحديث الكمية + السعر
                    $newQuantity = $existingDetail->quantity + $detail['quantity'];
                    $existingDetail->update([
                        'quantity' => $newQuantity,
                        'available_quantity' => $newQuantity,
                        'price' => getUnitPrice($detail['product_id'], $detail['unit_id']),
                    ]);
                } else {
                    // إدراج تفصيل جديد
                    $detail['order_id'] = $orderId;
                    $detail['price'] = getUnitPrice($detail['product_id'], $detail['unit_id']);
                    $detail['package_size'] = getUnitPricePackageSize($detail['product_id'], $detail['unit_id']);
                    OrderDetails::create($detail);
                }
            }
            $message = $pendingOrderId > 0
                ? 'Products added to pending approval order #' . $pendingOrderId
                : 'New order created successfully';

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
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
                $order->store_id = $branch->valid_store_id;
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
