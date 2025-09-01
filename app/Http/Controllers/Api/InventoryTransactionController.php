<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryTransactionController extends Controller
{
    public function index()
    {
        $transactions = InventoryTransaction::with(['transactionable'])->latest()->get();
        return response()->json(['data' => $transactions]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'unit_id' => 'required|exists:units,id',
            'package_size' => 'nullable|numeric',
            
            
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $transaction = InventoryTransaction::create($validated);
            DB::commit();
            return response()->json(['data' => $transaction, 'message' => 'Transaction created successfully'], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating transaction', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $transaction = InventoryTransaction::with(['transactionable'])->findOrFail($id);
        return response()->json(['data' => $transaction]);
    }

    public function update(Request $request, $id)
    {
        $transaction = InventoryTransaction::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'sometimes|numeric',
            'package_size' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $transaction->update($validated);
            DB::commit();
            return response()->json(['data' => $transaction, 'message' => 'Transaction updated successfully']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating transaction', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $transaction = InventoryTransaction::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $transaction->delete();
            DB::commit();
            return response()->json(['message' => 'Transaction deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting transaction', 'error' => $e->getMessage()], 500);
        }
    }
}