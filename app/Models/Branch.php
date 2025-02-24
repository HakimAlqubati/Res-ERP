<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Branch extends Model implements HasMedia
{

    use HasFactory, SoftDeletes, DynamicConnection,InteractsWithMedia;



    protected $fillable = [
        'id',
        'name',
        'address',
        'manager_id',
        'active',
        'is_hq',
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
    public function equipments()
    {
        return $this->hasMany(Equipment::class);
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
            } else if (isStuff()) {
                static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('id', auth()->user()->branch_id); // Add your default query here
                });
            }
        }
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function location()
    {
        return $this->morphOne(Location::class, 'locationable');
    }
}
