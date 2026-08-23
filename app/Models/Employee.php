<?php

namespace App\Models;

use App\Models\EmployeeServiceTermination;
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

    public function serviceTermination()
    {
        return $this->hasOne(EmployeeServiceTermination::class);
    }
    
    public function paymentMethod()
    {
        return $this->belongsTo(EmployeePaymentMethod::class, 'payment_method_id');
    }
    
    public function pendingTerminationRequest()
    {
        return $this->hasOne(EmployeeServiceTermination::class)->pending();
    }

    protected $table = 'hr_employees';

    protected $fillable = [
        'name',
        'known_name',
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
        'is_indexed_in_aws',
        'is_mtd_applicable',
        'has_auto_weekly_leave',
        'max_weekly_leave_days',
        'no_shift_is_present',
        'birthday',
        'salary_allocation_rule',
        'can_add_branch_order',
        'allow_attendance_from_any_branch',
        'created_by',
        'updated_by',
        'payment_method_id',
        'payment_details',
        'dynamic_field_values',
        'emergency_number',
    ];

    // ─────────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (auth()->check()) {
                $model->created_by = $model->created_by ?? auth()->id();
                $model->updated_by = $model->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    protected $auditInclude = [
        'name',
        'known_name',
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
        'gender',
        'nationality',
        'passport_no',
        'mykad_number',
        'tax_identification_number',
        'has_employee_pass',
        'working_hours',
        'manager_id',
        'working_days',
        'is_indexed_in_aws',
        'is_mtd_applicable',
        'has_auto_weekly_leave',
        'max_weekly_leave_days',
        'no_shift_is_present',
        'birthday',
        'can_add_branch_order',
        'allow_attendance_from_any_branch',
        'dynamic_field_values',
        'emergency_number',
    ];

    protected $casts = [
        'payment_details'                  => 'array',
        'dynamic_field_values'             => 'array',
        'changes'                          => 'array',
        'is_mtd_applicable'                => 'boolean',
        'has_auto_weekly_leave'            => 'boolean',
        'can_add_branch_order'             => 'boolean',
        'allow_attendance_from_any_branch' => 'boolean',
        'max_weekly_leave_days'            => 'integer',
        'no_shift_is_present'              => 'boolean',
        'salary_allocation_rule'           => \App\Enums\HR\Payroll\SalaryAllocationRule::class,
        'emergency_number'                 => \App\Casts\EmergencyContactCast::class,
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Get the effective salary allocation rule for the employee.
     * Fallbacks to system setting if no employee override exists.
     */
    public function getEffectiveSalaryAllocationRule(): \App\Enums\HR\Payroll\SalaryAllocationRule
    {
        if ($this->salary_allocation_rule) {
            return $this->salary_allocation_rule;
        }

        $systemSetting = settingWithDefault('payroll_salary_allocation_rule', \App\Enums\HR\Payroll\SalaryAllocationRule::PROPORTIONAL->value);

        return \App\Enums\HR\Payroll\SalaryAllocationRule::tryFrom($systemSetting)
            ?? \App\Enums\HR\Payroll\SalaryAllocationRule::PROPORTIONAL;
    }

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

    /**
     * Scope to find employees eligible for payroll in a specific period.
     * Eligible if:
     * 1. Joined before or during the period.
     * 2. Has salary > 0.
     * 3. Either Active OR was terminated during/after the period.
     */
    public function scopeEligibleForPayroll($query, $year, $month)
    {
        $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return $query->where('salary', '>', 0)
            ->where('join_date', '<=', $periodEnd->toDateString())
            ->where(function ($q) use ($periodStart) {
                $q->active()
                    ->orWhereHas('serviceTermination', function ($sub) use ($periodStart) {
                        $sub->where('status', EmployeeServiceTermination::STATUS_APPROVED)
                            ->where('termination_date', '>=', $periodStart->toDateString());
                    });
            });
    }
}
