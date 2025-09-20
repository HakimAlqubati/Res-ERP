<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResellerSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'store_id',
        'sale_date',
        'total_amount',
        'note',
        'created_by',
        'is_cancelled',
        'cancel_reason',
    ];

    protected $appends = [
    'item_count',
];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(ResellerSaleItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updateTotalAmount()
    {
        $this->total_amount = $this->items()->sum('total_price');
        $this->save();
    }

    public function paidAmounts()
    {
        return $this->hasMany(ResellerSalePaidAmount::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->paidAmounts()->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->total_paid;
    }

    public function ensureTotalAmountIsCorrect(): void
    {
        $itemsTotal = $this->items()->sum('total_price');

        if (is_null($this->total_amount) || round($this->total_amount, 2) !== round($itemsTotal, 2)) {
            $this->updateTotalAmount();
        }
    }

    protected static function booted()
    {
        static::retrieved(function (ResellerSale $sale) {
            $sale->ensureTotalAmountIsCorrect();
        });
    }

    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }
}
