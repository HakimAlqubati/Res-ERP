<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthSalary extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hr_month_salaries';

    protected $fillable = [
        'name', 
        'start_month',
        'end_month',
        'notes',
        'payment_date',
        'approved',
        'created_by',
        'branch_id',
        'month',
    ];

    // Relationship: One month salary has many details
    public function details()
    {
        return $this->hasMany(MonthSalaryDetail::class, 'month_salary_id');
    }
    public function deducationDetails()
    {
        return $this->hasMany(MonthlySalaryDeductionsDetail::class, 'month_salary_id');
    }
    public function increaseDetails()
    {
        return $this->hasMany(MonthlySalaryIncreaseDetail::class, 'month_salary_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class,'created_by');
    }

    protected static function booted()
    {
        if (isBranchManager()) {
            static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
            });
        }
    }
}
