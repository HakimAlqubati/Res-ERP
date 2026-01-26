<?php

namespace App\Models;

use App\Observers\EmployeeObserver;
use App\Traits\EmployeeAccessorsTrait;
use App\Traits\EmployeeAttendanceTrait;
use App\Traits\EmployeeRelationships;
use App\Traits\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy([EmployeeObserver::class])]
class Employee extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, BranchScope;
    use EmployeeRelationships, EmployeeAccessorsTrait, EmployeeAttendanceTrait;

    protected $table = 'hr_employees';

    protected $fillable = [
        'name',
        'position_id',
        'email',
        'phone_number',
        'job_title',
        'user_id',
        'branch_id',
        'department_id',
        'employee_no',
        'active',
        'avatar',
        'join_date',
        'address',
        'salary',
        'discount_exception_if_attendance_late',
        'discount_exception_if_absent',
        'rfid',
        'employee_type',
        'bank_account_number',
        'bank_information',
        'gender',
        'nationality',
        'passport_no',
        'mykad_number',
        'tax_identification_number',
        'has_employee_pass',
        'working_hours',
        'manager_id',
        'is_ceo',
        'working_days',
    ];

    protected $auditInclude = [
        'name',
        'position_id',
        'email',
        'phone_number',
        'job_title',
        'user_id',
        'branch_id',
        'department_id',
        'employee_no',
        'active',
        'avatar',
        'join_date',
        'address',
        'salary',
        'discount_exception_if_attendance_late',
        'discount_exception_if_absent',
        'rfid',
        'employee_type',
        'bank_account_number',
        'bank_information',
        'gender',
        'nationality',
        'passport_no',
        'mykad_number',
        'tax_identification_number',
        'has_employee_pass',
        'working_hours',
        'manager_id',
        'working_days',
    ];

    protected $casts = [
        'bank_information' => 'array',
        'changes'          => 'array',
    ];

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const TYPE_ACTION_EMPLOYEE_PERIOD_LOG_ADDED   = 'added';
    public const TYPE_ACTION_EMPLOYEE_PERIOD_LOG_REMOVED = 'removed';



    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeEmployeeTypesManagers($query)
    {
        return;
        return $query->whereIn('employee_type', [1, 2, 3]);
    }

    
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
