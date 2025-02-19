<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'reason_id',
        'adjustment_type', // Increase or Decrease
        'notes',
        'created_by',
        'adjustment_date',
    ];

    // Constants for adjustment types
    const ADJUSTMENT_TYPE_INCREASE = 'increase';
    const ADJUSTMENT_TYPE_DECREASE = 'decrease';


    public function details()
    {
        return $this->hasMany(StockAdjustmentDetail::class, 'stock_adjustment_id');
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function reason()
    {
        return $this->belongsTo(StockAdjustmentReason::class, 'reason_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor to format adjustment type.
     */
    public function getAdjustmentTypeTitleAttribute()
    {
        return ucfirst($this->adjustment_type); // Converts "increase" to "Increase"
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($stockAdjustment) {
            $stockAdjustment->created_by = auth()->id();
        });
        static::created(function ($stockAdjustment) {});
    }
}
