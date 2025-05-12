<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnedOrder extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_CREATED  = 'created';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'original_order_id',
        'branch_id',
        'reason',
        'returned_date',
        'status',
        'approved_by',
        'created_by',
        'store_id',
    ];

    protected $casts = [
        'returned_date' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(ReturnedOrderDetail::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'original_order_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_CREATED  => 'Created',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function getTotalQuantityAttribute(): float
    {
        return $this->details->sum('quantity');
    }
    public function getItemsCountAttribute(): float
    {
        return $this->details->count();
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->details->sum(fn($d) => $d->quantity * $d->price);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
