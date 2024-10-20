<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeApplication extends Model
{
    use HasFactory, SoftDeletes;
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
        'details', // json
        'rejected_reason',
    ];

    // Constants for application types
    const APPLICATION_TYPES = [
        1 => 'Leave request',
        2 => 'Missed check-in',
        3 => 'Advance request',
        4 => 'Missed check-out',
    ];

    // Constants for application types
    const APPLICATION_TYPE_LEAVE_REQUEST = 1;
    const APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST = 2;
    const APPLICATION_TYPE_ADVANCE_REQUEST = 3;
    const APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST = 4;

    const APPLICATION_TYPE_NAMES = [
        self::APPLICATION_TYPE_LEAVE_REQUEST => 'Leave request',
        self::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => 'Missed check-in',
        self::APPLICATION_TYPE_ADVANCE_REQUEST => 'Advance request',
        self::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => 'Missed check-out',
    ];

    // Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class); // Assuming you have an Employee model
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by'); // Assuming a User model
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by'); // Assuming a User model
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by'); // Assuming a User model
    }

    public function getDetailTimeAttribute()
    {
        if (in_array($this->application_type_id, [2, 4])) {
            // Decode the details JSON to an associative array
            $details = json_decode($this->details, true);
            // Return the detail_time if it exists
            return $details['detail_time'] ?? null;
        }

        return null;
    }
    public function getDetailDateAttribute()
    {
        if (in_array($this->application_type_id, [2, 3, 4])) {
            // Decode the details JSON to an associative array
            $details = json_decode($this->details, true);
            // Return the detail_date if it exists
            return $details['detail_date'] ?? null;
        }
        return null;
    }
    public function getDetailMonthlyDeductionAmountAttribute()
    {
        if ($this->application_type_id == 3) {
            // Decode the details JSON to an associative array
            $details = json_decode($this->details, true);
            // Return the detail_monthly_deduction_amount if it exists
            return $details['detail_monthly_deduction_amount'] ?? null;
        }
        return null;
    }
    public function getDetailAdvanceAmountAttribute()
    {
        if ($this->application_type_id == 3) {
            $details = json_decode($this->details, true);
            return $details['detail_advance_amount'] ?? null;
        }
        return null;
    }
    public function getDetailDeductionStartsFromAttribute()
    {
        if ($this->application_type_id == 3) {
            $details = json_decode($this->details, true);
            return $details['detail_deduction_starts_from'] ?? null;
        }
        return null;
    }
    public function getDetailDeductionEndsAtAttribute()
    {
        if ($this->application_type_id == 3) {
            $details = json_decode($this->details, true);
            return $details['detail_deduction_ends_at'] ?? null;
        }
        return null;
    }
    public function getDetailNumberOfMonthsOfDeductionAttribute()
    {
        if ($this->application_type_id == 3) {
            $details = json_decode($this->details, true);
            return $details['detail_number_of_months_of_deduction'] ?? null;
        }
        return null;
    }
    
    public function getDetailFromDateAttribute()
    {
        if ($this->application_type_id == 1) {
            $details = json_decode($this->details, true);
            return $details['detail_from_date'] ?? null;
        }
        return null;
    }
    public function getDetailToDateAttribute()
    {
        if ($this->application_type_id == 1) {
            $details = json_decode($this->details, true);
            return $details['detail_to_date'] ?? null;
        }
        return null;
    }
    public function getDetailLeaveTypeIdAttribute()
    {
        if ($this->application_type_id == 1) {
            $details = json_decode($this->details, true);
            return $details['detail_leave_type_id'] ?? null;
        }
        return null;
    }
    public function getDetailDaysCountAttribute()
    {
        if ($this->application_type_id == 1) {
            $details = json_decode($this->details, true);
            return $details['detail_days_count'] ?? null;
        }
        return null;
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
