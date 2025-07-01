<?php

namespace App\Models;

use App\Traits\Inventory\CanCancelStockSupplyOrder;
use Illuminate\Database\Eloquent\Casts\Attribute;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class StockSupplyOrder extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, CanCancelStockSupplyOrder;

    protected $fillable = [
        'order_date',
        'store_id',
        'notes',
        'cancelled',
        'cancel_reason',
        'created_using_model_id', // Ensure it's fillable
        'created_using_model_type', // Ensure it's fillable
        'cancelled_by',
    ];
    protected $auditInclude = [
        'order_date',
        'store_id',
        'notes',
        'cancelled',
        'cancel_reason',
        'created_using_model_id', // Ensure it's fillable
        'created_using_model_type',
        'cancelled_by',
    ];
    protected $appends = ['item_count', 'has_outbound_transactions']; // Appending the custom attribute

    public function store()
    {
        return $this->belongsTo(Store::class);
    }



    public function details()
    {
        return $this->hasMany(StockSupplyOrderDetail::class, 'stock_supply_order_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($stockSupplyOrder) {
            $stockSupplyOrder->created_by = auth()->id();
        });
    }

    /**
     * Accessor for item count.
     *
     * @return int
     */
    public function getItemCountAttribute()
    {
        return $this->details()->count();
    }
    public function createdUsingModel(): MorphTo
    {
        return $this->morphTo();
    }
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function hasOutboundTransactionsFromInbound(): bool
    {
        // Step 1: Get IDs of inbound transactions created from this supply order
        $inboundTransactionIds = \App\Models\InventoryTransaction::where('transactionable_type', self::class)
            ->where('transactionable_id', $this->id)
            ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
            ->pluck('id');

        // Step 2: Check if any outbound transaction used these as source_transaction_id
        return \App\Models\InventoryTransaction::whereIn('source_transaction_id', $inboundTransactionIds)
            ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_OUT)
            ->exists();
    }

    protected function hasOutboundTransactions(): Attribute
    {
        return Attribute::get(function () {
            $inboundTransactionIds = \App\Models\InventoryTransaction::where('transactionable_type', self::class)
                ->where('transactionable_id', $this->id)
                ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
                ->pluck('id');

            return \App\Models\InventoryTransaction::whereIn('source_transaction_id', $inboundTransactionIds)
                ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_OUT)
                ->exists();
        });
    }
    public function handleCancellation($order, string $reason): array
    {
        return $this->cancelStockSupplyOrder($order, $reason);
    }
}