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
}