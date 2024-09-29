<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Circular extends Model
{
    use SoftDeletes;

    protected $table = 'hr_circulars';

    protected $fillable = [
        'title',
        'description',
        'group_id',
        'branch_ids',
        'released_date',
        'active',
        'created_by',
        'updated_by',
    ];

    public function photos()
    {
        return $this->hasMany(CircularPhoto::class);
    }

    public function getPhotosCountAttribute()
    {
        return $this->photos()->count();
    }

    public function group()
    {
        return $this->belongsTo(UserType::class, 'group_id');
    }

    protected static function booted()
    {

        if (!isSuperAdmin() && !isSystemManager()) {

            static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $userType = auth()->user()->user_type;
                $branchId = auth()->user()->branch_id;
                // dd($branchId,$userType);
                $builder->where('group_id', $userType)
                    ->whereJsonContains('branch_ids', (string) $branchId); // Search within the branch_ids as a JSON array

            });
        }
    }
}
