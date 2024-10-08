<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeOvertime extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'hr_employee_overtime';

     // Fillable fields for mass assignment
     protected $fillable = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'rate',
        'reason',
        'approved',
        'approved_by',
        'notes',
        'created_by',
        'updated_by',
        'branch_id',
    ];

    // Relationships
    // Relationship with the Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Relationship with the User model (for approval)
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Relationship with the User model (who created the overtime entry)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with the User model (who updated the overtime entry)
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted()
    {
        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
                });
            } elseif (isStuff()) {
                static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('employee_id', auth()->user()->employee->id); // Add your default query here
                });
            }
        }
    }
}
