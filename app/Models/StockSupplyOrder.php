<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class StockSupplyOrder extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'order_date',
        'store_id',
        'notes',
        'cancelled',
        'cancel_reason',
        'created_using_model_id', // Ensure it's fillable
        'created_using_model_type', // Ensure it's fillable
    ];
    protected $auditInclude = [
        'order_date',
        'store_id',
        'notes',
        'cancelled',
        'cancel_reason',
        'created_using_model_id', // Ensure it's fillable
        'created_using_model_type', // Ensure it's fillable
    ];
    protected $appends = ['item_count']; // Appending the custom attribute

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
}
