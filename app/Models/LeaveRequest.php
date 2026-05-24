<?php

namespace App\Models;

use App\Observers\LeaveRequestObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy([LeaveRequestObserver::class])]
class LeaveRequest extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'hr_leave_requests';

    protected $fillable = [
        'application_type_id',
        'application_type_name',
        'application_id',
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'leave_type_id',
        'year',
        'month',
        'days_count',
    ];
    protected $auditInclude = [
        'application_type_id',
        'application_type_name',
        'application_id',
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'leave_type_id',
        'year',
        'month',
        'days_count',
    ];

    /**
     * الطلب الأب (EmployeeApplicationV2)
     */
    public function application()
    {
        return $this->belongsTo(EmployeeApplicationV2::class, 'application_id');
    }

    /**
     * نوع الإجازة (LeaveType)
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type');
    }

    public function deletedLeaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type')->withTrashed();
    }
}
