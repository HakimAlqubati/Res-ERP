<?php

namespace App\Traits;

use App\Models\ApplicationTransaction;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeBranchLog;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeFaceData;
use App\Models\EmployeeFile;
use App\Models\EmployeeMonthlyIncentive;
use App\Models\EmployeeOvertime;
use App\Models\EmployeePeriod;
use App\Models\EmployeePeriodDay;
use App\Models\EmployeePeriodHistory;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\PenaltyDeduction;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkPeriod;
use Carbon\Carbon;

/**
 * Trait containing all relationship methods for Employee model.
 */
trait EmployeeRelationships
{
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

    public function leaveApplications()
    {
        return $this->hasMany(EmployeeApplicationV2::class, 'employee_id')
            ->where('hr_employee_applications.application_type_id', 1)
            ->with('leaveRequest');
    }

    public function approvedLeaveApplications()
    {
        return $this->hasMany(EmployeeApplicationV2::class, 'employee_id')
            ->where('hr_employee_applications.application_type_id', 1)
            ->where('status', EmployeeApplicationV2::STATUS_APPROVED)
            ->with('leaveRequest');
    }

    public function transactions()
    {
        return $this->hasMany(ApplicationTransaction::class, 'employee_id');
    }

    public function approvedAdvanceApplication()
    {
        return $this->hasMany(EmployeeApplicationV2::class, 'employee_id')
            ->where('status', EmployeeApplicationV2::STATUS_APPROVED)
            ->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST);
    }

    public function approvedLeaveApplication()
    {
        return $this->hasMany(EmployeeApplicationV2::class, 'employee_id')
            ->where('status', EmployeeApplicationV2::STATUS_APPROVED)
            ->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST);
    }

    public function periods()
    {
        return $this->belongsToMany(WorkPeriod::class, 'hr_employee_periods', 'employee_id', 'period_id')
            ->withPivot('id');
    }

    public function employeePeriods()
    {
        return $this->hasMany(EmployeePeriod::class, 'employee_id', 'id');
    }

    public function periodHistories()
    {
        return $this->hasMany(EmployeePeriodHistory::class);
    }

    public function advancedInstallments()
    {
        return $this->hasMany(EmployeeAdvanceInstallment::class);
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
        return $this->hasMany(EmployeeOvertime::class, 'employee_id')
            ->where('approved', 1)
            ->day();
    }

    public function overtimesByDate($date)
    {
        return $this->hasMany(EmployeeOvertime::class, 'employee_id')
            ->day()
            ->where('approved', 1)
            ->where('date', $date);
    }

    public function overtimesofMonth($date)
    {
        $startOfMonth = Carbon::parse($date)->startOfMonth()->toDateString();
        $endOfMonth   = Carbon::parse($date)->endOfMonth()->toDateString();

        return $this->hasMany(EmployeeOvertime::class, 'employee_id')
            ->day()
            ->where('approved', 1)
            ->whereBetween('date', [$startOfMonth, $endOfMonth]);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class)
            ->where('deleted_at', null);
    }

    public function attendancesByDate($date)
    {
        return $this->hasMany(Attendance::class)
            ->accepted()
            ->where('deleted_at', null)
            ->where('check_date', $date);
    }

    public function branchLogs()
    {
        return $this->hasMany(EmployeeBranchLog::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id', 'id');
    }

    public function managedDepartment()
    {
        return $this->hasOne(Department::class, 'manager_id');
    }

    public function managers()
    {
        return $this->hasManyThrough(Employee::class, Department::class, 'id', 'department_id', 'department_id', 'manager_id');
    }

    public function periodDays()
    {
        return $this->hasManyThrough(
            EmployeePeriodDay::class,
            EmployeePeriod::class,
            'employee_id',
            'employee_period_id',
            'id',
            'id'
        );
    }

    public function faceData()
    {
        return $this->hasMany(EmployeeFaceData::class, 'employee_id');
    }

    public function leaveTypes()
    {
        return $this->belongsToMany(LeaveType::class, 'hr_leave_balances', 'employee_id', 'leave_type_id')
            ->withPivot(['year', 'month', 'balance']);
    }

    public function approvedPenaltyDeductions()
    {
        return $this->hasMany(PenaltyDeduction::class)->where('status', 'approved');
    }

    public function mealRequests()
    {
        return $this->hasMany(EmployeeMealRequest::class, 'employee_id');
    }
}
