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

    // Tax brackets for nationality 'MY'
    public const TAX_BRACKETS = [
        [0, 5000, 0],
        [5001, 20000, 1],
        [20001, 35000, 3],
        [35001, 50000, 8],
        [50001, 70000, 13],
        [70001, 100000, 21],
        [100001, 250000, 24],
        [250001, 400000, 25],
        [400001, 600000, 26],
        [600001, 1000000, 28],
        [1000001, 2000000, 30],
        [2000001, PHP_INT_MAX, 32],
    ];

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

    // ─────────────────────────────────────────────────────────────
    // Boot Method
    // ─────────────────────────────────────────────────────────────

    protected static function booted()
    {
        if (isBranchManager()) {
            // Scoping for branch managers if needed
        } elseif (isStuff()) {
            static::addGlobalScope(function (Builder $builder) {
                // Scoping for staff if needed
            });
        }
    }
}
