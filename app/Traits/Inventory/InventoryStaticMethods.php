<?php

namespace App\Traits\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

trait InventoryStaticMethods
{

    public static function getInventoryTrackingDataPagination(
        $productId,
        $perPage = 15,
        ?string $movementType = null,
        $unitId = null,
        $storeId = null,
        ?string $transactionableType = null
    ) {
        $query = InventoryTransaction::query() // Using Eloquent query instead of DB::table()
            ->whereNull('deleted_at')
            ->where('product_id', $productId);
        if (!empty($movementType)) {
            $query->where('movement_type', $movementType);
        }
        if (!empty($unitId)) {
            $query->where('unit_id', $unitId);
        }

        if (!empty($storeId)) {
            $query->where('store_id', $storeId);
        }
        if (!empty($transactionableType)) {
            $query->where('transactionable_type', $transactionableType);
        }
        return  $query->orderBy('id', 'asc')
            ->paginate($perPage);
    }


    public static function getInventoryTrackingData($productId)
    {
        $transactions = InventoryTransaction::query() // Using Eloquent instead of DB::table()
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->orderBy('movement_date', 'asc')
            ->get();

        $trackingData = [];
        $remainingQty = 0;

        foreach ($transactions as $transaction) {
            $quantityImpact = $transaction->quantity * $transaction->package_size;
            $remainingQty += ($transaction->movement_type === InventoryTransaction::MOVEMENT_IN) ? $quantityImpact : -$quantityImpact;

            $trackingData[] = [
                'date' => $transaction->movement_date,
                'type' => $transaction->formatted_transactionable_type, // Now it works!
                'quantity' => $transaction->quantity,
                'unit_id' => $transaction->unit_id,
                'unit_name' => $transaction->unit?->name ?? '',
                'package_size' => $transaction->package_size,
                'quantity_impact' => $quantityImpact,
                'remaining_qty' => $remainingQty,
                'transactionable_id' => $transaction->transactionable_id,
                'notes' => $transaction->notes,
            ];
        }

        return $trackingData;
    }


    /**
     * Get the remaining inventory for a given product and unit (static version).
     *
     * @param int $productId
     * @param int $unitId
     * @return float
     */
    public static function getInventoryRemaining($productId, $unitId)
    {
        $totalIn = DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->sum(DB::raw('quantity * package_size'));

        $totalOut = DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->sum(DB::raw('quantity * package_size'));

        return $totalIn - $totalOut;
    }


    public static function moveToStore(array $data): InventoryTransaction
    {
        return InventoryTransaction::create([
            'product_id'           => $data['product_id'],
            'movement_type'        => $data['movement_type'], // use InventoryTransaction::MOVEMENT_IN / OUT
            'quantity'             => $data['quantity'],
            'unit_id'              => $data['unit_id'],
            'package_size'         => $data['package_size'] ?? 1,
            'store_id'             => $data['store_id'],
            'price'                => $data['price'] ?? 0,
            'transaction_date'     => $data['transaction_date'] ?? now(),
            'movement_date'        => $data['movement_date'] ?? now(),
            'notes'                => $data['notes'] ?? null,
            'transactionable_id'   => $data['transactionable']?->id ?? null,
            'transactionable_type' => $data['transactionable'] ? get_class($data['transactionable']) : null,
        ]);
    }
}
