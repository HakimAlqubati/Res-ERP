<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class InventoryTransaction extends Model
{
    use SoftDeletes;
    // Table name
    protected $table = 'inventory_transactions';

    // Fillable fields
    protected $fillable = [
        'product_id',
        'movement_type',
        'quantity',
        'unit_id',
        'movement_date',
        'notes',
        'package_size',
        'store_id',
        'price',
        'transaction_date',
        'purchase_invoice_id',
        'transactionable_id',
        'transactionable_type',
    ];
    protected $appends = ['remaining_qty', 'movement_type_title', 'formatted_transactionable_type'];

    // Constant movement types
    const MOVEMENT_OUT = 'out';
    const MOVEMENT_IN = 'in';

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public static function getInventoryTrackingDataPagination($productId, $perPage = 15)
    {
        return self::query() // Using Eloquent query instead of DB::table()
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->orderBy('movement_date', 'asc')
            ->paginate($perPage);
    }



    public static function getInventoryTrackingData($productId)
    {
        $transactions = self::query() // Using Eloquent instead of DB::table()
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->orderBy('movement_date', 'asc')
            ->get();

        $trackingData = [];
        $remainingQty = 0;

        foreach ($transactions as $transaction) {
            $quantityImpact = $transaction->quantity * $transaction->package_size;
            $remainingQty += ($transaction->movement_type === self::MOVEMENT_IN) ? $quantityImpact : -$quantityImpact;

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
            ->where('movement_type', self::MOVEMENT_IN)
            ->sum(DB::raw('quantity * package_size'));

        $totalOut = DB::table('inventory_transactions')
            ->where('product_id', $this->product_id)
            ->where('unit_id', $this->unit_id)
            ->where('movement_type', self::MOVEMENT_OUT)
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
            ->where('movement_type', self::MOVEMENT_IN)
            ->sum(DB::raw('quantity * package_size'));

        $totalOut = DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('movement_type', self::MOVEMENT_OUT)
            ->sum(DB::raw('quantity * package_size'));

        return $totalIn - $totalOut;
    }

    public function getMovementTypeTitleAttribute()
    {
        return $this->movement_type == static::MOVEMENT_IN ? 'In' : 'Out';
    }

    // Define Polymorphic Relation
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function boot()
    {
        parent::boot();

        // When retrieving the model, modify the `transactionable_type`
        static::retrieved(function ($transaction) {
            if ($transaction->transactionable_type) {
                $transaction->transactionable_type = class_basename($transaction->transactionable_type);
            }
        });
    }

    // public function getFormattedTransactionableTypeAttribute()
    // {
    //     return $this->transactionable_type ? class_basename($this->transactionable_type) : null;
    // }

    public function getFormattedTransactionableTypeAttribute()
    {
        if (!$this->transactionable_type) {
            return null;
        }

        // Convert "StockSupplyOrder" to "Stock Supply Order"
        return preg_replace('/(?<!\ )[A-Z]/', ' $0', class_basename($this->transactionable_type));
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
