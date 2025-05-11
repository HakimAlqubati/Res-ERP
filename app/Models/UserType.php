<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'role_ids',
        'active',
        'can_access_all_branches',
        'can_access_all_stores',
        'can_access_non_branch_data',
        'hide_prices',
    ];

    // Define role_ids as a JSON column
    protected $casts = [
        'role_ids' => 'array',
        'active' => 'boolean',
        'can_access_all_branches' => 'boolean',
        'can_access_all_stores' => 'boolean',
        'can_access_non_branch_data' => 'boolean',
        'hide_prices' => 'boolean',

    ];

    public function getRoleNamesAttribute(): string
    {
        $roles = \Spatie\Permission\Models\Role::whereIn('id', $this->role_ids ?? [])->pluck('name')->toArray();
        return implode(', ', $roles);
    }


    protected static function booted()
    {
        // dd(auth()->check());
        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->whereIn('id', [2, 3, 4]);
                });
            }
        }
    }
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }
}
