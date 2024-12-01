<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlySalaryIncreaseDetail extends Model
{
    use HasFactory;

    protected $table = 'hr_monthly_salary_increases_details';

    public const TYPE_BONUS = 'bonus';
    public const TYPE_INCENTIVE = 'incentive';
    public const TYPE_ALLOWANCE = 'allowance';

    public const TYPE_MONTHLY_LEAVE_ALLOWANCE_ID = -1;
    public const TYPE_MONTHLY_LEAVE_ALLOWANCE_Name = 'Weekend overtime';
    protected $fillable = [
        'month_salary_id',
        'employee_id',
        'is_specific_employee',
        'type',
        'type_id',
        'name',
        'amount',
    ];

    // Optional: Define relationships if necessary
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function monthSalary()
    {
        return $this->belongsTo(MonthSalary::class, 'month_salary_id');
    }

    public const ALLOWANCE_TYPES = [
        self::TYPE_MONTHLY_LEAVE_ALLOWANCE_ID => self::TYPE_MONTHLY_LEAVE_ALLOWANCE_Name,
    ];
}
