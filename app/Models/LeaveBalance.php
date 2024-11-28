<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends Model
{
    use HasFactory,SoftDeletes;

    // Define the table name if it's different from the convention
    protected $table = 'hr_leave_balances';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'balance',
        'created_by',
        'branch_id',
        'month',
    ];

    /**
     * Relationship to the Employee model
     * Each leave balance belongs to an employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship to the LeaveType model
     * Each leave balance belongs to a specific leave type
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public static function getBalanceForEmployee($employeeId, $leaveTypeId,$year)
    {
        return self::where('employee_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->where('year', $year)
                    ->first();
    }
    public static function getMonthlyBalanceForEmployee($employeeId, $leaveTypeId,$year,$month)
    {
        return self::where('employee_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();
    }

    protected static function booted()
    { 
        // parent::boot();
      
    //    dd(auth()->user(),auth()->user()->has_employee,auth()->user()->employee);
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
