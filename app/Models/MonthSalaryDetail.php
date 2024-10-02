<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthSalaryDetail extends Model
{
    use HasFactory;

    protected $table = 'hr_month_salary_details';

    protected $fillable = [
        'month_salary_id',
        'employee_id',
        'basic_salary',
        'total_deductions',
        'total_allowances',
        'total_incentives',
        'overtime_hours',
        'overtime_pay',
        'net_salary',
    ];

    // Relationship: Each salary detail belongs to a month salary
    public function monthSalary()
    {
        return $this->belongsTo(MonthSalary::class, 'month_salary_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
