<?php

namespace App\Models;

use App\Observers\EmployeeServiceTerminationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([EmployeeServiceTerminationObserver::class])]
class EmployeeServiceTermination extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_employee_service_terminations';

    protected $fillable = [
        'employee_id',
        'termination_date',
        'termination_reason',
        'notes',
        'status',
        'rejection_reason',
        'created_by',
        'updated_by',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'termination_date' => 'date',
        'approved_at'      => 'datetime',
        'rejected_at'      => 'datetime',
    ];

    // Status Constants
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
