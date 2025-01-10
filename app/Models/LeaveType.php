<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hr_leave_types';

    protected $fillable = [
        'name',
        'count_days',
        'description',
        'active',
        'created_by',
        'updated_by',
        'type',
        'balance_period',
        'is_paid',
    ];

    protected $appends = ['type_label', 'balance_period_label'];

    // Enum constants for 'type'
    const TYPE_YEARLY = 'yearly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_SPECIAL = 'special';

    const BALANCE_PERIOD_YEARLY = 'yearly';
    const BALANCE_PERIOD_MONTHLY = 'monthly';
    const BALANCE_PERIOD_OTHER = 'other';
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
        return $query->where('type', static::TYPE_WEEKLY)
            ->where('balance_period', static::BALANCE_PERIOD_MONTHLY)
            ->get()
            ->sum(function ($leaveType) {
                return $leaveType->count_days ?? 4;
            });
    }


    /**
     * Helper function to get a human-readable label for the leave type.
     *
     * @return string
     */
    public function getTypeLabelAttribute()
    {
        return self::getTypes()[$this->type] ?? 'Unknown Type';
    }

    public function getBalancePeriodLabelAttribute()
    {
        return self::getBalancePeriods()[$this->balance_period] ?? 'Unknown Period';
    }

    public static function getTypes()
    {
        return [
            self::TYPE_YEARLY => 'Annual Leave',
            self::TYPE_MONTHLY => 'Monthly Leave',
            self::TYPE_WEEKLY => 'Weekly Leave',
            self::TYPE_SPECIAL => 'Special Leave'
        ];
    }

    public static function getBalancePeriods()
    {
        return [
            self::BALANCE_PERIOD_YEARLY => 'Annual',
            self::BALANCE_PERIOD_MONTHLY => 'Monthly',
            self::BALANCE_PERIOD_OTHER => 'Other'
        ];
    }

    public function scopeWeeklyLeave($query)
    {
        return $query->where('type', LeaveType::TYPE_WEEKLY)
            ->where('balance_period', LeaveType::BALANCE_PERIOD_MONTHLY)
            ->where('active', 1)->first()
        ;
    }
}
