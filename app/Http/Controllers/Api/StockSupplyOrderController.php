<?php

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\StockSupplyOrder;
use App\Models\StockSupplyOrderDetail;
use App\Models\UnitPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockSupplyOrderController extends Controller
{
    public function index(Request $request)
    {

        $otherBranchesCategories = Branch::centralKitchens()
            ->where('id', '!=', auth()->user()->branch->id) // نستثني فرع المستخدم
            ->with('categories:id')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();


        $query = StockSupplyOrder::with(['store', 'details.product', 'details.unit'])
            ->orderBy('created_at', 'desc');

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        $query->where('created_by', auth()->user()->id);
        if (isBranchManager()) {

            if (!isStoreManager() && auth()->user()->branch->is_kitchen) {
                $query->whereHas('details.product.category', function ($q) use ($otherBranchesCategories) {

                    $q->where('is_manafacturing', 1)
                        ->whereNotIn('categories.id', $otherBranchesCategories);
                });
            }
        }
        if (!isStoreManager() && auth()->user()->branch->is_kitchen) {
            $query->with(['details' => function ($q) use ($otherBranchesCategories) {
                $q->whereHas('product.category', function ($q2) use ($otherBranchesCategories) {
                    $q2->where('is_manafacturing', true)
                        ->whereNotIn('categories.id', $otherBranchesCategories);
                });
            }]);
        }
        $orders = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.unit_id' => 'required|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get the branch and check if it is a central kitchen
            $branch = auth()->user()->branch;

            if ($branch->is_central_kitchen) {
                $storeId = $branch->store_id;
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This branch is not a central kitchen and cannot place a supply order.'
                ], 422);
            }

            $order = StockSupplyOrder::create([
                'order_date' => $request->order_date,
                'store_id' => $storeId, // Use store_id from branch
                'notes' => $request->notes,
            ]);

            foreach ($request->details as $detail) {
                // Get package_size from unit_prices table
                $unitPrice = UnitPrice::where('product_id', $detail['product_id'])
                    ->where('unit_id', $detail['unit_id'])
                    ->first();

                if (!$unitPrice) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unit price not found for the given product and unit'
                    ], 422);
                }

                StockSupplyOrderDetail::create([
                    'stock_supply_order_id' => $order->id,
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                    'package_size' => $unitPrice->package_size, // Automatically set package_size
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Supply order created successfully',
                'data' => $order->load(['store', 'details.product', 'details.unit'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create supply order',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $order = StockSupplyOrder::with(['store', 'details.product', 'details.unit'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $order
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supply order not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'order_date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.unit_id' => 'required|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0',
            'details.*.package_size' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $order = StockSupplyOrder::findOrFail($id);

            if ($order->cancelled) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update cancelled order'
                ], 422);
            }

            $order->update([
                'order_date' => $request->order_date,
                'store_id' => $request->store_id,
                'notes' => $request->notes,
            ]);

            // Delete existing details
            $order->details()->delete();

            // Create new details
            foreach ($request->details as $detail) {
                StockSupplyOrderDetail::create([
                    'stock_supply_order_id' => $order->id,
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                    'package_size' => $detail['package_size'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Supply order updated successfully',
                'data' => $order->load(['store', 'details.product', 'details.unit'])
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update supply order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $order = StockSupplyOrder::findOrFail($id);
            $order->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Supply order deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete supply order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = StockSupplyOrder::findOrFail($id);

            if ($order->cancelled) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order is already cancelled'
                ], 422);
            }

            $order->update([
                'cancelled' => true,
                'cancel_reason' => $request->cancel_reason
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Supply order cancelled successfully',
                'data' => $order->load(['store', 'details.product', 'details.unit'])
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel supply order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
