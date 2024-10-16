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

    public static function getBalanceForEmployee($employeeId, $leaveTypeId)
    {
        return self::where('employee_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->first();
    }
}
