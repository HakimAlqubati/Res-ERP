<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePeriodHistory extends Model
{
    use HasFactory,DynamicConnection;

    protected $table = 'hr_employee_period_histories';
    protected $fillable = [
        'employee_id',
        'period_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'active',
    ];

    // Define the relationship with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Define the relationship with WorkPeriod
    public function workPeriod()
    {
        return $this->belongsTo(WorkPeriod::class, 'period_id');
    }
}
