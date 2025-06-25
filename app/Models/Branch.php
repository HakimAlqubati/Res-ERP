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
        'start_date',
        'end_date',
        'more_description',
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
        'start_date',
        'end_date',
        'more_description',
    ];
    protected $appends = [
        'customized_categories',
        'orders_count',
        'reseller_balance',
        'total_paid',
        'total_sales',
        'total_orders_amount',
        'is_kitchen'
    ];

    // ✅ Constants
    public const TYPE_BRANCH = 'branch';
    public const TYPE_CENTRAL_KITCHEN = 'central_kitchen';
    public const TYPE_HQ = 'hq';
    public const TYPE_POPUP = 'popup';
    public const TYPE_RESELLER = 'reseller';
    // ✅ Optional: Array of allowed types
    public const TYPES = [
        self::TYPE_BRANCH,
        self::TYPE_CENTRAL_KITCHEN,
        self::TYPE_HQ,
        self::TYPE_POPUP,
        self::TYPE_RESELLER
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
        $data['is_central_kitchen'] = (int) $this->isKitchen;
        $data['customized_categories'] = $this->categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
            ];
        });
        return $data;
    }

    public function getisCentralKitchenAttribute(): bool
    {
        return $this->type === self::TYPE_CENTRAL_KITCHEN;
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

    public function scopeNormal($query)
    {
        return $query->whereIn('type', [
            self::TYPE_BRANCH,
            self::TYPE_HQ,
        ]);
    }

    public function scopeWithAllTypes($query)
    {
        return $query->whereIn('type', self::TYPES);
    }

    public function scopePopups($query)
    {
        return $query->where('type', self::TYPE_POPUP);
    }

    public function getIsPopupAttribute(): bool
    {
        return $this->type === self::TYPE_POPUP;
    }
    public function getTypeTitleAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BRANCH => __('lang.branch'),
            self::TYPE_CENTRAL_KITCHEN => __('lang.central_kitchen'),
            self::TYPE_HQ => __('lang.hq'),
            self::TYPE_POPUP => __('lang.popup_branch'),
            self::TYPE_RESELLER => __('lang.reseller'),
            default => __('lang.unknown'),
        };
    }

    public function scopeActivePopups($query)
    {
        return $query->where(function ($q) {
            $q->where('type', '!=', self::TYPE_POPUP)
                ->orWhere(function ($q2) {
                    $q2->where('type', self::TYPE_POPUP)
                        ->where('end_date', '>=', now()->format('Y-m-d'));
                });
        });
    }

    public function getCustomizedCategoriesAttribute()
    {
        return $this->categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
            ];
        });
    }

    public function getOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    public function paidAmounts()
    {
        return $this->hasMany(BranchPaidAmount::class);
    }

    public function salesAmounts()
    {
        return $this->hasMany(BranchSalesAmount::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->paidAmounts->sum('amount');
    }

    public function getTotalSalesAttribute(): float
    {
        return $this->salesAmounts->sum('amount');
    }

    public function getResellerBalanceAttribute(): float
    {
        return $this->total_sales - $this->total_paid;
    }

    public function getTotalOrdersAmountAttribute(): float
    {
        return $this->orders()
            ->with('orderDetails') // مهم لتفادي N+1
            ->get()
            ->sum(function ($order) {
                return $order->total_amount; // accessor في Order
            });
    }
}