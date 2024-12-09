<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlySalaryDeductionsDetail extends Model
{
    use HasFactory;

    // Specify the table name if it doesn't follow Laravel's convention
    protected $table = 'hr_monthly_salary_deductions_details';

    public const LATE_HOUR_DEDUCTIONS = -1;
    public const ABSENT_DAY_DEDUCTIONS = -2;
    public const ADVANCED_MONTHLY_DEDUCATION = -3;
    
    // public const TAX_DEDUCTIONS = -4;
    public const EARLY_DEPATURE_EARLY_HOURS = -5;
    
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

    public const DEDUCTION_TYPES = [
        self::LATE_HOUR_DEDUCTIONS => 'Late hour deduction',
        self::ABSENT_DAY_DEDUCTIONS => 'Absent day deduction',
        self::ADVANCED_MONTHLY_DEDUCATION => 'Advance deduction',
        // self::TAX_DEDUCTIONS => 'MTD', 
        self::EARLY_DEPATURE_EARLY_HOURS => 'Early departure deduction', 
    ];
}
