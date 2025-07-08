<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePeriodDay extends Model
{
    use HasFactory;

    protected $table = 'employee_period_days';

    protected $fillable = [
        'employee_id',
        'period_id',
        'day_of_week',
        'start_date',
        'end_date',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function period()
    {
        return $this->belongsTo(WorkPeriod::class, 'period_id');
    }

    public function workPeriod()
    {
        return $this->belongsTo(WorkPeriod::class, 'period_id');
    }

}