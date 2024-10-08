<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlySalaryDeductionsDetail extends Model
{
    use HasFactory;

    // Specify the table name if it doesn't follow Laravel's convention
    protected $table = 'hr_monthly_salary_deductions_details';

    // Specify the fillable fields
    protected $fillable = [
        'month_salary_id',
        'employee_id',
        'is_specific_employee',
        'deduction_id',
        'deduction_name',
        'deduction_amount',
        'is_percentage',
        'amount_value',
        'percentage_value',
    ];

    // Optional: Define relationships if necessary
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function deduction()
    {
        return $this->belongsTo(Deduction::class, 'deduction_id');
    }

    public function monthSalary()
    {
        return $this->belongsTo(MonthSalary::class, 'month_salary_id');
    }
}
