<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Branch extends Model implements HasMedia, Auditable
{

    use HasFactory, SoftDeletes, DynamicConnection, InteractsWithMedia, \OwenIt\Auditing\Auditable;



    protected $fillable = [
        'id',
        'name',
        'address',
        'manager_id',
        'active',


        'store_id',
        'manager_abel_show_orders',

        'type',
    ];
    protected $auditInclude = [
        'id',
        'name',
        'address',
        'manager_id',
        'active',


        'store_id',
        'manager_abel_show_orders',

        'type',
    ];
    // ✅ Constants
    public const TYPE_BRANCH = 'branch';
    public const TYPE_CENTRAL_KITCHEN = 'central_kitchen';
    public const TYPE_HQ = 'hq';

    // ✅ Optional: Array of allowed types
    public const TYPES = [
        self::TYPE_BRANCH,
        self::TYPE_CENTRAL_KITCHEN,
        self::TYPE_HQ,
    ];
    // protected $casts = [

    // ];

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
                    // $builder->where('id', auth()->user()->branch_id); // Add your default query here
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

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    public function toArray()
    {
        $data = parent::toArray();
        return $data;
    }

    public function getValidStoreIdAttribute(): ?int
    {
        if (
            $this->isKitchen &&
            $this->categories()->exists() &&
            $this->store
        ) {
            return $this->store_id;
        }
        if (
            auth()->check() &&
            $this->manager_id === auth()->id() &&
            $this->isKitchen &&
            $this->store
        ) {
            return $this->store_id;
        }
        return null;
    }


    public function scopeCentralKitchens($query)
    {
        return $query->where('type', self::TYPE_CENTRAL_KITCHEN);
    }
    public function scopeBranches($query)
    {
        return $query->where('type', self::TYPE_BRANCH);
    }
    public function scopeHQBranches($query)
    {
        return $query->where('type', self::TYPE_HQ);
    }
    public function getIsKitchenAttribute(): bool
    {
        return $this->type === self::TYPE_CENTRAL_KITCHEN;
    }

    public function getIsBranchAttribute(): bool
    {
        return $this->type === self::TYPE_BRANCH;
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'branch_category', 'branch_id', 'category_id');
    }

    public function getCategoryNamesAttribute()
    {
        return $this->categories->pluck('name')->toArray();
    }
}
