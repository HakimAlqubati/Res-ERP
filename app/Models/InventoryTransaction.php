<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryTransaction extends Model
{
    // Table name
    protected $table = 'inventory_transactions';

    // Fillable fields
    protected $fillable = [
        'product_id',
        'movement_type',
        'quantity',
        'unit_id',
        'movement_date',
        'reference_id',
        'notes',
        'package_size',
    ];
    protected $appends = ['remaining_qty', 'movement_type_title'];

    // Constant movement types
    const MOVEMENT_ORDERS = 'orders';
    const MOVEMENT_PURCHASE_INVOICE = 'purchase_invoice';

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public static function getInventoryTrackingData($productId)
    {
        $transactions = DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->orderBy('movement_date', 'asc')
            
            ->get();

        $trackingData = [];
        $remainingQty = 0;

        foreach ($transactions as $transaction) {
            
            $quantityImpact = $transaction->quantity * $transaction->package_size;
            $remainingQty += ($transaction->movement_type === self::MOVEMENT_PURCHASE_INVOICE) ? $quantityImpact : -$quantityImpact;

            $trackingData[] = [
                'date' => $transaction->movement_date,
                'type' => $transaction->movement_type === self::MOVEMENT_PURCHASE_INVOICE ? 'Purchase' : 'Order',
                'quantity' => $transaction->quantity,
                'unit_id' => $transaction->unit_id,
                'unit_name' => Unit::find($transaction->unit_id)->name,
                'package_size' => $transaction->package_size,
                'quantity_impact' => $quantityImpact,
                'remaining_qty' => $remainingQty,
                'reference_id' => $transaction->reference_id,
                'notes' => $transaction->notes,
            ];
        }

        return $trackingData;
    }

    /**
     * Get the remaining inventory for a given product and unit.
     * Remaining quantity = (sum of incoming quantity * package_size) - (sum of outgoing quantity * package_size)
     *
     * @return float
     */
    public function getRemainingQtyAttribute()
    {
        $totalIn = DB::table('inventory_transactions')
            ->where('product_id', $this->product_id)
            ->where('unit_id', $this->unit_id)
            ->where('movement_type', self::MOVEMENT_PURCHASE_INVOICE)
            ->sum(DB::raw('quantity * package_size'));

        $totalOut = DB::table('inventory_transactions')
            ->where('product_id', $this->product_id)
            ->where('unit_id', $this->unit_id)
            ->where('movement_type', self::MOVEMENT_ORDERS)
            ->sum(DB::raw('quantity * package_size'));

        return $totalIn - $totalOut;
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
            ->where('movement_type', self::MOVEMENT_PURCHASE_INVOICE)
            ->sum(DB::raw('quantity * package_size'));

        $totalOut = DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('movement_type', self::MOVEMENT_ORDERS)
            ->sum(DB::raw('quantity * package_size'));

        return $totalIn - $totalOut;
    }

    public function getMovementTypeTitleAttribute()
    {
        return $this->movement_type == static::MOVEMENT_PURCHASE_INVOICE ? 'In' : 'Out';
    }
}
