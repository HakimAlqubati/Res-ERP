<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
    ];

    public const TYPE_ACTION_EMPLOYEE_PERIOD_LOG_ADDED = 'added';
    public const TYPE_ACTION_EMPLOYEE_PERIOD_LOG_REMOVED = 'removed';

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function files()
    {
        return $this->hasMany(EmployeeFile::class, 'employee_id');
    }

    // Accessor for required_documents_count
    public function getRequiredDocumentsCountAttribute()
    {
        return $this->files()->whereHas('fileType', function ($query) {
            $query->where('is_required', true);
        })->count();
    }

    // Accessor for unrequired_documents_count
    public function getUnrequiredDocumentsCountAttribute()
    {
        return $this->files()->whereHas('fileType', function ($query) {
            $query->where('is_required', false);
        })->count();
    }

    public function getAvatarImageAttribute()
    {
        $filePath = 'public/' . $this->avatar;
        if (!$this->avatar) {
            return url('/storage') . '/' . 'employees/default/avatar.png';
        }
        if (Storage::exists($filePath)) {
            return url('/storage') . '/' . $this->avatar;
        }
        return  url('/storage') . '/' . 'employees/default/avatar.png';
    }

    public function approvedLeaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'employee_id')->where('status', LeaveApplication::STATUS_APPROVED)->with('leaveType');
    }

    public function periods()
    {
        return $this->belongsToMany(WorkPeriod::class, 'hr_employee_periods', 'employee_id', 'period_id');
    }

    // Log changes to periods
    public function logPeriodChange(array $periodIds, $action)
    {
        EmployeePeriodLog::create([
            'employee_id' => $this->id,
            'period_ids' => json_encode($periodIds), // Store as JSON
            'action' => $action,
        ]);
    }

    // Apply the global scope
    protected static function booted()
    {
        if (isBranchManager()) {
            static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
            });
        } elseif (isStuff()) {
            static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                // $builder->where('id', auth()->user()->employee->id); // Add your default query here
            });
        }
    }

    public function getHasUserAttribute()
    {
        if ($this->user()->exists()) {
            return true;
        }
        return false;
    }

    public function monthlyIncentives()
    {
        return $this->hasMany(EmployeeMonthlyIncentive::class);
    }

    public function allowances()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function deductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function overtimes()
    {
        return $this->hasMany(EmployeeOvertime::class, 'employee_id')->where('approved', 1);
    }

}
