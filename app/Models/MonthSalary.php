<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthSalary extends Model
{
    use HasFactory;
    protected $table = 'hr_month_salaries';

    protected $fillable = [
        'name',
        'month',
        'start_month',
        'end_month',
        'notes',
        'payment_date',
        'approved',
    ];

    // Relationship: One month salary has many details
    public function details()
    {
        return $this->hasMany(MonthSalaryDetail::class, 'month_salary_id');
    }
}
