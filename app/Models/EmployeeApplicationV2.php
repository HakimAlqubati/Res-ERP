<?php

namespace App\Models;

use App\Observers\EmployeeApplicationObserver;
use App\Traits\EmployeeApplicationAccessors;
use App\Traits\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy([EmployeeApplicationObserver::class])]
class EmployeeApplicationV2 extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, BranchScope, EmployeeApplicationAccessors;

    protected $appends = [
        'leave_type_name',
        'leave_type_id',
    ];

    protected $table = 'hr_employee_applications';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'application_date',
        'status',
        'notes',
        'application_type_id',
        'application_type_name',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'details',
        'rejected_reason',
    ];

    protected $auditInclude = [
        'employee_id',
        'branch_id',
        'application_date',
        'status',
        'notes',
        'application_type_id',
        'application_type_name',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'details',
        'rejected_reason',
    ];

    // Application type constants
    const APPLICATION_TYPE_LEAVE_REQUEST = 1;
    const APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST = 2;
    const APPLICATION_TYPE_ADVANCE_REQUEST = 3;
    const APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST = 4;
    const APPLICATION_TYPE_MEAL_REQUEST = 5;

    const APPLICATION_TYPES = [
        1 => 'Leave request',
        2 => 'Missed check-in',
        3 => 'Advance request',
        4 => 'Missed check-out',
        5 => 'Employee Meals Request',
    ];

    const APPLICATION_TYPE_NAMES = [
        self::APPLICATION_TYPE_LEAVE_REQUEST => 'Leave request',
        self::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => 'Missed check-in',
        self::APPLICATION_TYPE_ADVANCE_REQUEST => 'Advance request',
        self::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => 'Missed check-out',
        self::APPLICATION_TYPE_MEAL_REQUEST => 'Employee Meals Request',
    ];

    const APPLICATION_TYPE_FILTERS = [
        self::APPLICATION_TYPE_LEAVE_REQUEST => '?tab=Leave+request',
        self::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => '?tab=Missed+check-in',
        self::APPLICATION_TYPE_ADVANCE_REQUEST => '?tab=Advance+request',
        self::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => '?tab=Missed+check-out',
        self::APPLICATION_TYPE_MEAL_REQUEST => '?tab=Employee+Meals+Request',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function advanceInstallments()
    {
        return $this->hasMany(EmployeeAdvanceInstallment::class, 'application_id');
    }

    public function missedCheckinRequest()
    {
        return $this->hasOne(MissedCheckInRequest::class, 'application_id');
    }

    public function missedCheckoutRequest()
    {
        return $this->hasOne(MissedCheckOutRequest::class, 'application_id');
    }

    public function leaveRequest()
    {
        return $this->hasOne(LeaveRequest::class, 'application_id');
    }

    public function advanceRequest()
    {
        return $this->hasOne(AdvanceRequest::class, 'application_id');
    }

    public function mealRequest()
    {
        return $this->hasOne(EmployeeMealRequest::class, 'application_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Computed Attributes
    // ─────────────────────────────────────────────────────────────

    public function getPaidInstallmentsCountAttribute()
    {
        return $this->advanceInstallments()->where('is_paid', true)->count();
    }
}
