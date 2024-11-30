<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'active', 'manager_id', 'parent_id'];

    // Relationship to Employee (Manager of the department)
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    // Parent department (if applicable)
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    // Child departments (sub-departments)
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    // Scope to get all top-level (root) departments (those without a parent)
    public function scopeRootDepartments($query)
    {
        return $query->whereNull('parent_id');
    }
}
