<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;
    protected $table = 'hr_leave_types';

    protected $fillable = [
        'name',
        'count_days',
        'description',
        'active',
        'created_by',
        'updated_by',
        'is_monthly',
        'used_as_weekend',
    ];

    // Relationship to the user who created the leave type
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to the user who updated the leave type
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }


    /**
     * Scope to get the sum of monthly count days, defaulting null values to 4.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return int
     */
    public function scopeGetMonthlyCountDaysSum($query)
    {
        return $query->where('is_monthly', 1)
            ->get()
            ->sum(function ($leaveType) {
                return $leaveType->count_days ?? 4;
            });
    }
}
