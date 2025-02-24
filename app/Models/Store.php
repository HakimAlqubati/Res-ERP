<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'location',
        'active',
        'default_store',
        'storekeeper_id',
    ];

    protected $appends = ['storekeeper_name'];
    /**
     * Scope to get only active stores.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get the default store.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefaultStore($query)
    {
        return $query->where('default_store', true)->first();
    }

    public function storekeeper()
    {
        return $this->belongsTo(User::class, 'storekeeper_id');
    }

    public function getStorekeeperNameAttribute()
    {
        return $this->storekeeper->name ?? '';
    }

    public function scopeWithManagedStores($query)
    {
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
}
