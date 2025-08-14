<?php

namespace App\Http\Controllers;

use App\Jobs\CopyOutToInForOrdersJob;
use App\Models\InventoryTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CopyOutToInApiController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['integer', 'min:1'],
        ]);

        $branchId = $data['branch_id'];

        if (!$branchId) {
            return response()->json([
                'ok' => false,
                'queued' => false,
                'branch_id' => $branchId,
                'message' => 'Branch ID is required.',
            ]);
        }

        // نستدعي نفس الـ Job الموجودة لديك: (tenant=null, branch=$branchId)
        // CopyOutToInForOrdersJob::dispatch(null, $branchId);
        Order::select(['id', 'branch_id', 'created_at'])
            ->with(['branch:id,store_id', 'branch.store:id'])
            ->whereNull('deleted_at')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))

            ->whereHas('branch.store')
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    $store = $order->branch?->store;
                    if (! $store) {
                        continue; // لا يوجد مخزن للفرع
                    }
                    DB::transaction(function () use ($store, $order) {

                        InventoryTransaction::where('transactionable_type', Order::class)
                            ->where('transactionable_id', $order->id)
                            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                            ->where('store_id', $store->id)
                            ->withTrashed()
                            ->forceDelete();
                        $outTransactions = InventoryTransaction::where('transactionable_type', Order::class)
                            ->where('transactionable_id', $order->id)
                            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                            ->get(['id', 'product_id', 'quantity', 'unit_id', 'package_size', 'price', 'store_id']);
                        if ($outTransactions->isEmpty()) return;
                        $rows = [];
                        foreach ($outTransactions as $out) {
                            $rows[] = [
                                'product_id' => $out->product_id,
                                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                                'quantity' => $out->quantity,
                                'unit_id' => $out->unit_id,
                                'movement_date' => $order->created_at,
                                'transaction_date' => $order->created_at,
                                'package_size' => $out->package_size,
                                'price' => $out->price,
                                'notes' => 'Supplied from Order #' . $order->id,
                                'store_id' => $store->id,
                                'transactionable_type' => Order::class,
                                'transactionable_id' => $order->id,
                                'source_transaction_id' => $out->id,
                            ];
                        }
                        if ($rows) {
                            DB::table('inventory_transactions')->insert($rows);
                        }
                    });
                }
            });

        return response()->json([
            'ok' => true,
            'queued' => true,
            'branch_id' => $branchId,
            'message' => 'Job dispatched to queue "inventory".',
        ]);
    }
}
