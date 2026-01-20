<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class EmployeeMealRequest extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'hr_employee_meal_requests';

    protected $fillable = [
        'application_id',
        'employee_id',
        'branch_id',
        'meal_details',
        'cost',
        'notes',
        'date',
        'created_by',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $auditInclude = [
        'application_id',
        'employee_id',
        'branch_id',
        'meal_details',
        'cost',
        'notes',
        'date',
        'created_by',
        'status',
        'approved_by',
        'approved_at',
    ];

    /**
     * Get the parent application.
     */
    public function application()
    {
        return $this->belongsTo(EmployeeApplicationV2::class, 'application_id');
    }

    /**
     * Get the employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the request.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
