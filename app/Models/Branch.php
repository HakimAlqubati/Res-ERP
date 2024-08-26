<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'manager_id',
        'active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function getTotalQuantityAttribute()
    {
        return $this->orders()
            ->join('orders_details', 'orders_details.order_id', '=', 'orders.id')
            ->whereIn('orders.status', [Order::DELEVIRED,  Order::READY_FOR_DELEVIRY])
            ->sum('orders_details.available_quantity');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
