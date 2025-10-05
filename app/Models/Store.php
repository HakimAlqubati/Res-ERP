<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;

class Store extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'name',
        'location',
        'active',
        'default_store',
        'storekeeper_id',
        'is_central_kitchen',
    ];
    protected $auditInclude = [
        'name',
        'location',
        'active',
        'default_store',
        'storekeeper_id',
        'is_central_kitchen',
    ];

    protected $appends = ['storekeeper_name'];
    /**
     * Scope to get only active stores.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get the default store.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDefaultStore($query)
    {
        return $query->where('default_store', true)->active()->first();
    }
    public function scopeCentralKitchen($query)
    {
        return $query->where('is_central_kitchen', true)->first();
    }

    public function storekeeper()
    {
        return $this->belongsTo(User::class, 'storekeeper_id');
    }

    public function scopeCentralKitchenStores($query)
    {
        if (auth()->user()?->branch?->is_kitchen) {
            return $query->where('id', auth()->user()->branch->store_id);
        };
    }

    public function getStorekeeperNameAttribute()
    {
        return $this->storekeeper->name ?? '';
    }

    public function scopeWithManagedStores($query)
    {
        if (isFinanceManager()) {
            return $query;
        }
        if (isStoreManager()) {
            return $query->whereIn('id', auth()->user()->managed_stores_ids);
        } else {
            return $query;
        }
    }


    public static function boot()
    {
        parent::boot();

        static::updating(function ($store) {
            if ($store->default_store) {
                // Unset the previous default store
                Store::where('default_store', true)
                    ->where('id', '!=', $store->id) // Exclude the current store
                    ->update(['default_store' => false]);
            }
            // if ($store->is_central_kitchen) {
            //     // Unset the previous default store
            //     Store::where('is_central_kitchen', true)
            //         ->where('id', '!=', $store->id) // Exclude the current store
            //         ->update(['is_central_kitchen' => false]);
            // }
        });

        // static::saving(function ($store) {
        //     if ($store->default_store) {
        //         // Check if there is already a default store
        //         $existingDefaultStore = Store::where('default_store', true)
        //             ->where('id', '!=', $store->id) // Exclude the current store being updated
        //             ->exists();

        //         if ($existingDefaultStore) {
        //             throw new \Exception('Only one default store is allowed.');
        //         }
        //     }
        // });
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'store_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_store');
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(\App\Models\PurchaseInvoice::class);
    }

    /**
     * يعرِض فقط المخازن التي لديها مشتريات (Purchase Invoices) غير محذوفة.
     */
    public function scopeHasPurchases($query)
    {
        return $query->whereExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('purchase_invoices as pi')
                ->whereColumn('pi.store_id', 'stores.id')
                ->whereNull('pi.deleted_at'); // تجاهُل المحذوفة سوفت
        });
    }
}
