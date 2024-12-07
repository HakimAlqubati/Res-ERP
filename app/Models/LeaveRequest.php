<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
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
}
