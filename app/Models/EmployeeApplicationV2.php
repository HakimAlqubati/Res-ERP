<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use App\Traits\Scopes\BranchScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class EmployeeApplicationV2 extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, BranchScope;

    protected $appends = [
        'detailed_leave_request',
        'detailed_advance_application',
        'detailed_missed_checkin_application',
        'DetailedMissedCheckoutApplication',

    ];

    // protected $with = [
    //     'employee',
    //     'createdBy',
    //     'missedCheckoutRequest',
    //     'missedCheckinRequest',
    //     'advanceRequest',
    //     'leaveRequest',
    // ];


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
    const APPLICATION_TYPE_FILTERS = [
        self::APPLICATION_TYPE_LEAVE_REQUEST => '?activeTab=Leave+request',
        self::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => '?activeTab=Missed+check-in',
        self::APPLICATION_TYPE_ADVANCE_REQUEST => '?activeTab=Advance+request',
        self::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => '?activeTab=Missed+check-out',
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


    protected static function booted()
    {
        // parent::boot();

        //    dd(auth()->user(),auth()->user()->has_employee,auth()->user()->employee);
        // static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
        //     $builder->where('branch_id', auth()->user()->branch_id)->where('application_type_id', 1); // Add your default query here
        // });
        // if (auth()->check()) {
        //     if (isBranchManager()) {
        //     } elseif (isStuff() || isFinanceManager()) {
        //         static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
        //             $builder->where('employee_id', auth()->user()->employee->id); // Add your default query here
        //         });
        //     }
        // }
    }

    public function advanceInstallments()
    {
        return $this->hasMany(EmployeeAdvanceInstallment::class, 'application_id');
    }

    public function getPaidInstallmentsCountAttribute()
    {
        return $this->advanceInstallments()->where('is_paid', true)->count();
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

    public function getDetailedLeaveRequestAttribute()
    {
        $details = $this->details; // Assuming `details` is the column where JSON data is stored

        // Decode the JSON string into an array
        $detailsArray = json_decode($details, true);

        // Check if decoding was successful and return a detailed array
        if (is_array($detailsArray)) {
            return [
                'id' => $this->id,
                'leave_type_id' => $detailsArray['detail_leave_type_id'] ?? null,
                'from_date' => isset($detailsArray['detail_from_date'])
                    ? Carbon::parse($detailsArray['detail_from_date'])->format('Y-m-d')
                    : null,
                'to_date' => isset($detailsArray['detail_to_date'])
                    ? Carbon::parse($detailsArray['detail_to_date'])->format('Y-m-d')
                    : null,
                'days_count' => $detailsArray['detail_days_count'] ?? null,
                'year' => $detailsArray['detail_year'] ?? null,
                'month' => $detailsArray['detail_month'] ?? null,
            ];
        }

        // If decoding fails, return null or an empty array
        return [];
    }

    public function getDetailedAdvanceApplicationAttribute()
    {
        $details = $this->details; // Assuming `details` is the name of the column

        // Decode the JSON string into an array
        $detailsArray = json_decode($details, true);

        // Check if decoding was successful and return a detailed array
        if (is_array($detailsArray)) {

            return [
                'id' => $this->id,
                'advance_amount' => isset($detailsArray['detail_advance_amount'])
                    ? number_format($detailsArray['detail_advance_amount'], 2)
                    : null,
                'monthly_deduction_amount' => isset($detailsArray['detail_monthly_deduction_amount'])
                    ? number_format($detailsArray['detail_monthly_deduction_amount'], 2)
                    : null,
                'deduction_ends_at' => isset($detailsArray['detail_deduction_ends_at'])
                    ? Carbon::parse($detailsArray['detail_deduction_ends_at'])->format('Y-m-d')
                    : null,
                'number_of_months_of_deduction' => $detailsArray['detail_number_of_months_of_deduction'] ?? null,
                'date' => isset($detailsArray['detail_date'])
                    ? Carbon::parse($detailsArray['detail_date'])->format('Y-m-d')
                    : null,
                'deduction_starts_from' => isset($detailsArray['detail_deduction_starts_from'])
                    ? Carbon::parse($detailsArray['detail_deduction_starts_from'])->format('Y-m-d')
                    : null,
            ];
        }

        // If decoding fails, return null or an empty array
        return [];
    }
    public function getDetailedMissedCheckinApplicationAttribute()
    {
        if ($this->application_type_id == 2) {
            $details = $this->details; // Assuming `details` is the name of the column

            // Decode the JSON string into an array
            $detailsArray = json_decode($details, true);

            // Check if decoding was successful and return a detailed array
            if (is_array($detailsArray)) {

                return [
                    'id' => $this->id,
                    'date' => isset($detailsArray['detail_date'])
                        ? ($detailsArray['detail_date'])
                        : null,
                    'time' => isset($detailsArray['detail_time'])
                        ? ($detailsArray['detail_time'])
                        : null,
                ];
            }

            // If decoding fails, return null or an empty array
            return [];
        }
    }
    public function getDetailedMissedCheckoutApplicationAttribute()
    {
        if ($this->application_type_id == 4) {

            $details = $this->details; // Assuming `details` is the name of the column

            // Decode the JSON string into an array
            $detailsArray = json_decode($details, true);

            // Check if decoding was successful and return a detailed array
            if (is_array($detailsArray)) {

                return [
                    'id' => $this->id,
                    'date' => isset($detailsArray['detail_date'])
                        ? ($detailsArray['detail_date'])
                        : null,
                    'time' => isset($detailsArray['detail_time'])
                        ? ($detailsArray['detail_time'])
                        : null,
                ];
            }

            // If decoding fails, return null or an empty array
            return [];
        }
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
    public function getDetailYearAttribute()
    {
        if ($this->application_type_id == 1) {
            $details = json_decode($this->details, true);
            return $details['detail_year'] ?? null;
        }
        return null;
    }
    public function getDetailMonthAttribute()
    {
        if ($this->application_type_id == 1) {
            $details = json_decode($this->details, true);
            return $details['detail_month'] ?? null;
        }
        return null;
    }
}
