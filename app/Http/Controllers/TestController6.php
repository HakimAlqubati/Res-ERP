<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Services\Reports\CenteralKitchens\InVsOutReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController6 extends Controller
{
    public function getInData(Request $request)
    {

        $filters = $request->only([
            'product_id',
            'store_id',
            'to_date',
        ]);

        $reportService = new InVsOutReportService();

        $data = $reportService->getInData($filters);
        return response()->json($data);
    }
    public function getOutData(Request $request)
    {

        $filters = $request->only([
            'product_id',
            'store_id',
            'to_date',
        ]);

        $reportService = new InVsOutReportService();

        $data = $reportService->getOutData($filters);
        return response()->json($data);
    }

    public function getFinalComparison(Request $request)
    {

        $filters = $request->only([
            'product_id',
            'store_id',
            'to_date',
        ]);

        $reportService = new InVsOutReportService();

        $data = $reportService->getFinalComparison($filters);
        return response()->json($data);
    }



    public function storeInventoryTransctionInForBranchStoresFromOrders()
    {
        $insertedCount = 0;
        $skippedCount = 0;
        $invalidOrders = 0;

        DB::transaction(function () use (&$insertedCount, &$skippedCount, &$invalidOrders) {
            $outTransactions = InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('transactionable_type', Order::class)
                ->get();

            foreach ($outTransactions as $out) {
                $order = Order::find($out->transactionable_id);

                if ($order->branch_id != 12) {
                    continue;
                }
                if (!$order || !$order->branch || !$order->branch->store || !$order->branch->store->active) {
                    $invalidOrders++;
                    continue;
                }

                $inExists = InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_IN)
                    ->where('source_transaction_id', $out->id)
                    ->where('transactionable_type', Order::class)
                    ->exists();

                if ($inExists) {
                    $skippedCount++;
                    continue;
                }

                InventoryTransaction::create([
                    'product_id'            => $out->product_id,
                    'movement_type'         => InventoryTransaction::MOVEMENT_IN,
                    'quantity'              => $out->quantity,
                    'unit_id'               => $out->unit_id,
                    'package_size'          => $out->package_size,
                    'price'                 => $out->price,
                    'movement_date'         => $out->movement_date,
                    'transaction_date'      => $out->transaction_date,
                    'store_id'              => $order->branch->store_id,
                    'notes'                 => "Stock received at branch store for Order #{$order->id}.",
                    'transactionable_id'    => $order->id,
                    'transactionable_type'  => Order::class,
                    'source_transaction_id' => $out->id,
                ]);

                $insertedCount++;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'IN transactions generated successfully.',
            'inserted_count' => $insertedCount,
            'skipped_existing' => $skippedCount,
            'skipped_invalid_orders' => $invalidOrders,
        ]);
    }
}
