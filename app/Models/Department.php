<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_departments';
    protected $fillable = [
        'name',
        'description',
        'active',
        'manager_id',
        'max_employees',
        'administration_id',
        'branch_id',
        'is_global',
        'parent_id',
    ];

    // Relationship to Employee (Manager of the department)
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function administration()
    {
        return $this->belongsTo(Administration::class, 'administration_id');
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

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    // دالة لجلب الأقسام الأبوية بشكل تكراري
    public function ancestors()
    {
        // إذا كان للقسم أب، استدعاء ancestors للقسم الأب بشكل تكراري
        $ancestors = collect();
        $current = $this;

        while ($current->parent) {
            $ancestors->push($current->parent);
            $current = $current->parent;
        }

        return $ancestors;
    }
}
