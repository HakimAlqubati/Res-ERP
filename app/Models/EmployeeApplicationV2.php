<?php

namespace App\Models;

use App\Facades\Warnings;
use App\Enums\Warnings\WarningLevel;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Services\Warnings\WarningPayload;
use Illuminate\Support\Facades\Log;

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
         'leave_type_name',      // جديد
        'leave_type_id',

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
        self::APPLICATION_TYPE_LEAVE_REQUEST => '?tab=Leave+request',
        self::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => '?tab=Missed+check-in',
        self::APPLICATION_TYPE_ADVANCE_REQUEST => '?tab=Advance+request',
        self::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => '?tab=Missed+check-out',
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
        parent::boot();
        static::created(function (EmployeeApplicationV2 $app) {
            try {
                // جلب الموظف مع مديره واليوزر الخاص بالمدير
                $employee = $app->employee()->with(['manager.user'])->first();

                if (!$employee || !$employee->manager || !$employee->manager->user) {
                    return; // لا يوجد مدير/يوزر -> لا إشعار
                }

                $managerUser = $employee->manager->user;

                // تجنّب إشعار الشخص نفسه (لو أنشأ الطلب هو نفسه المدير)
                if (auth()->check() && auth()->id() === $managerUser->id) {
                    return;
                }

                // عنوان ونص الإشعار
                $typeName = self::APPLICATION_TYPE_NAMES[$app->application_type_id] ?? 'Application';
                $title    = 'New Request from ' . ($employee->name ?? 'Employee');
                $lines    = [
                    "Type: {$typeName}",
                    "Date: " . ($app->application_date ?: now()->toDateString()),
                ];
                $body = implode("\n", $lines);

                // رابط شاشة الطلبات في لوحة التحكم + فلتر التبويب (إن وُجد)
                // عدّل مورد Filament أدناه لمسارك الفعلي إن كان مختلفًا:
                $baseUrl = EmployeeApplicationResource::getUrl();

                $filterSuffix = self::APPLICATION_TYPE_FILTERS[$app->application_type_id] ?? '';
                $url = rtrim($baseUrl, '/')  . $filterSuffix;

                // إرسال الإشعار
                Warnings::send(
                    $managerUser,
                    WarningPayload::make(
                        $title,
                        $body,
                        WarningLevel::Info
                    )
                        ->ctx([
                            'application_id' => $app->id,
                            'employee_id'    => $employee->id,
                            'type_id'        => $app->application_type_id,
                        ])
                        ->url($url)
                        ->scope("emp-app-{$app->id}")   // scope فريد لتجنّب التكرار
                        ->expires(now()->addHours(24))  // صلاحية الإشعار
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to notify manager for new EmployeeApplicationV2', [
                    'application_id' => $app->id ?? null,
                    'employee_id'    => $app->employee_id ?? null,
                    'error'          => $e->getMessage(),
                ]);
            }
        });

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


   
  

    public function getDetailTimeAttribute()
    {
        if ($this->application_type_id == 2) {
            return $this->missedCheckinRequest?->time;
        }

        if ($this->application_type_id == 4) {
            return $this->missedCheckoutRequest?->time;
        }

        return null;
    }

    public function getDetailDateAttribute()
    {
        if ($this->application_type_id == 2) {
            return $this->missedCheckinRequest?->date;
        }

        if ($this->application_type_id == 3) {
            $details = json_decode($this->details, true);
            return $details['detail_date'] ?? null;
        }

        if ($this->application_type_id == 4) {
            return $this->missedCheckoutRequest?->date;
        }

        return null;
    }



    public function getDetailMonthlyDeductionAmountAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->monthly_deduction_amount;
        }
        return null;
    }
    public function getDetailAdvanceAmountAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->advance_amount;
        }
        return null;
    }
    public function getDetailDeductionStartsFromAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->deduction_starts_from;
        }
        return null;
    }
    public function getDetailDeductionEndsAtAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->deduction_ends_at;
        }
        return null;
    }
    public function getDetailNumberOfMonthsOfDeductionAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->number_of_months_of_deduction;
        }
        return null;
    }

    public function getDetailFromDateAttribute()
    {
        if ($this->application_type_id == 1) {
            return $this->leaveRequest->start_date ?? null;
        }
        return null;
    }
    public function getDetailToDateAttribute()
    {
        if ($this->application_type_id == 1) {
            return $this->leaveRequest->end_date ?? null;
        }
        return null;
    }



    public function getLeaveTypeModelAttribute()
    {
        // نتحقق أن الطلب من نوع إجازة
        if ($this->application_type_id != self::APPLICATION_TYPE_LEAVE_REQUEST) {
            return null;
        }

        return $this->leaveRequest?->leaveType;
    }

    public function getLeaveTypeNameAttribute()
    {
        if ($this->application_type_id != self::APPLICATION_TYPE_LEAVE_REQUEST) {
            return null;
        }

        // dd($this->leaveRequest->leaveType);        // إذا كان عندك عمود name في جدول hr_leave_types
        return $this->leaveRequest?->leaveType?->name;
    }
    public function getLeaveTypeIdAttribute()
    {
        if ($this->application_type_id != self::APPLICATION_TYPE_LEAVE_REQUEST) {
            return null;
        }

        // dd($this->leaveRequest->leaveType);        // إذا كان عندك عمود name في جدول hr_leave_types
        return $this->leaveRequest?->leaveType?->id;
    }
    public function getDetailDaysCountAttribute()
    {
        if ($this->application_type_id == 1) {
            return $this->leaveRequest->days_count ?? null;
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
