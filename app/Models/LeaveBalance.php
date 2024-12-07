<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends Model
{
    use HasFactory, SoftDeletes;

    // Define the table name if it's different from the convention
    protected $table = 'hr_leave_balances';

    // protected $appends = ['balance'];

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'month',
        'balance',
        'branch_id',
        'created_by',
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

    public static function getBalanceForEmployee($employeeId, $leaveTypeId, $year)
    {
        return self::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();
    }
    public static function getMonthlyBalanceForEmployee($employeeId, $year, $month)
    {
        return self::where('employee_id', $employeeId)
            ->where('hr_leave_types.used_as_weekend', 1)
            ->join('hr_leave_types', 'hr_leave_balances.leave_type_id', '=', 'hr_leave_types.id')
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

    // public function getBalanceAttribute()
    // {
    //     return 50;
    // }
}
