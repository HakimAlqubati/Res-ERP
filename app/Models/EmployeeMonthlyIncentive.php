<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeMonthlyIncentive extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_monthly_incentives';
    protected $fillable = ['employee_id', 'monthly_incentive_id', 'amount'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function monthlyIncentive()
    {
        return $this->belongsTo(MonthlyIncentive::class, 'monthly_incentive_id');
    }
}
