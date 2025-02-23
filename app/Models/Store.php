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
}
