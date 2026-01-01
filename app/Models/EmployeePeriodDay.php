<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable;

class EmployeePeriodDay extends Model implements Auditable
{
    use HasFactory,\OwenIt\Auditing\Auditable;

    protected $table = 'hr_employee_period_days';

    protected $fillable = [
        'employee_period_id',
        'day_of_week',
        'start_date',
        'end_date',
    ];

    protected $auditInclude = [
        'employee_period_id',
        'day_of_week',
        'start_date',
        'end_date',
    ];

    // ✅ علاقة رئيسية: اليوم مرتبط بـ EmployeePeriod
    public function employeePeriod()
    {
        return $this->belongsTo(EmployeePeriod::class, 'employee_period_id');
    }

    // ✅ علاقات مساعدة (للوصول للموظف أو فترة العمل من خلال EmployeePeriod)

    public function employee()
    {
        return $this->hasOneThrough(
            Employee::class,
            EmployeePeriod::class,
            'id',          // Foreign key on EmployeePeriod
            'id',          // Foreign key on Employee
            'employee_period_id', // Local key on this model
            'employee_id'         // Local key on EmployeePeriod
        );
    }

    public function workPeriod()
    {
        return $this->hasOneThrough(
            WorkPeriod::class,
            EmployeePeriod::class,
            'id',
            'id',
            'employee_period_id',
            'period_id'
        );
    }
}