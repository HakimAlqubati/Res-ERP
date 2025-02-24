<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockIssueOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_date',
        'store_id',
        'created_by',
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(StockIssueOrderDetail::class, 'stock_issue_order_id');
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
