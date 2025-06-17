<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReturnedOrder;
use Illuminate\Http\Request;

class ReturnedOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnedOrder::with(['details.product', 'details.unit', 'order', 'branch', 'store', 'creator', 'approver']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->has('from_date')) {
            $query->whereDate('returned_date', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('returned_date', '<=', $request->input('to_date'));
        }

        $perPage = $request->input('per_page', 15);
        $results = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $results
        ]);
    }

    public function show($id)
    {
        $order = ReturnedOrder::with(['details.product', 'details.unit', 'order', 'branch', 'store', 'creator', 'approver'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $order
        ]);
    }
}
