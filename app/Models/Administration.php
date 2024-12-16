<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Administration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_administrations';
    protected $fillable = [
        'name',
        'manager_id',
        'description',
        'active',
        'is_global',
        'start_date',
        'branch_id'
    ];


    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }


    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }
}
