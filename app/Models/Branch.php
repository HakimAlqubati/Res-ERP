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
            ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
            ->sum('orders_details.available_quantity');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function areas()
    {
        return $this->hasMany(BranchArea::class);
    }

    public function scopeWithUserCheck($query)
    {
        $isSuperAdmin = isSuperAdmin();
        $isSystemManager = isSystemManager();
        $isBranchManager = isBranchManager();

        $isStuff = isStuff();

        if ($isSuperAdmin || $isSystemManager) {
            return $query;
        }

        if ($isBranchManager) {
            return $query->where('id', auth()->user()->branch->id);
        }

        if ($isStuff) {
            return $query->where('id', auth()->user()->branch_id);
        }
    }

    // Apply the global scope
    protected static function booted()
    {
        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('id', auth()->user()->branch_id); // Add your default query here
                });
            }
        }
    }
}
