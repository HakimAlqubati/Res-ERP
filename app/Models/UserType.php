<?php

namespace App\Models;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'role_ids', 'active',];

    // Define role_ids as a JSON column
    protected $casts = [
        'role_ids' => 'array',
        'active' => 'boolean',

    ];

    public function getRoleNamesAttribute(): string
    {
        $roles = Role::whereIn('id', $this->role_ids ?? [])->pluck('name')->toArray();
        return implode(', ', $roles);
    }


    protected static function booted()
    {
        // dd(auth()->check());
        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope(function (Builder $builder) {
                    $builder->whereIn('id', [2, 3, 4]);
                });
            }
        }
    }
}
