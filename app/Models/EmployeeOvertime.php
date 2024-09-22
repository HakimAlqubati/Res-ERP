<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertime extends Model
{
    use HasFactory;
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
        'updated_by'
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
}
