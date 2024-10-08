<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlySalaryIncreaseDetail extends Model
{
    use HasFactory;

    // Specify the table name if it doesn't follow Laravel's convention
    protected $table = 'hr_monthly_salary_increases_details';
// 'bonus', 'incentive', 'allowance' type fields
    // Specify the fillable fields
    protected $fillable = [
        'month_salary_id',
        'employee_id',
        'is_specific_employee',
        'type',
        'name',
        'amount',
    ];

    // Optional: Define relationships if necessary
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowance()
    {
        return $this->belongsTo(Allowance::class, 'allowance_id');
    }

    public function monthSalary()
    {
        return $this->belongsTo(MonthSalary::class, 'month_salary_id');
    }
}
