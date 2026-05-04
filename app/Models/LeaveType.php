<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class LeaveType extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
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
        'requires_attachment',
        'carry_forward_allowed',
        'max_carry_forward',
        'prorate_on_hire',
        'applicable_to'
    ];
    protected $auditInclude = [
        'name',
        'count_days',
        'description',
        'active',
        'created_by',
        'updated_by',
        'type',
        'balance_period',
        'is_paid',
        'requires_attachment',
        'carry_forward_allowed',
        'max_carry_forward',
        'prorate_on_hire',
        'applicable_to'
    ];

    protected $appends = ['type_label', 'balance_period_label'];

    protected $casts = [
        'requires_attachment' => 'boolean',
        'carry_forward_allowed' => 'boolean',
        'prorate_on_hire' => 'boolean',
        'max_carry_forward' => 'integer',
        'is_paid' => 'boolean',
        'active' => 'boolean',
    ];
    // Enum constants for 'type'
    const TYPE_YEARLY = 'yearly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_SPECIAL = 'special';

    const BALANCE_PERIOD_YEARLY = 'yearly';
    const BALANCE_PERIOD_MONTHLY = 'monthly';
    const BALANCE_PERIOD_OTHER = 'other';


    // Enum constants for applicability
    const APPLICABLE_ALL = 'all';
    const APPLICABLE_EXPAT_WITH_EP = 'expat_with_ep'; // Expatriate with Employment Pass

    public static function getApplicabilityOptions()
    {
        return [
            self::APPLICABLE_ALL => 'All Employees',
            self::APPLICABLE_EXPAT_WITH_EP => 'Expats with EP only',
        ];
    }
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
     * @param Builder $query
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
            // self::TYPE_WEEKLY => 'Weekly Leave',
            // self::TYPE_SPECIAL => 'Special Leave'
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

    /**
     * جميع طلبات الإجازات التي تستخدم هذا النوع
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }

    /**
     * الفروع المرتبطة بهذا النوع من الإجازات
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'hr_branch_leave_types', 'leave_type_id', 'branch_id');
    }

    /**
     * 2. حل مشكلة التكرار بين type و balance_period برمجياً
     * هذا الـ Event يعمل تلقائياً قبل الحفظ أو التعديل
     */
    protected static function booted()
    {
        static::saving(function ($leaveType) {
            // ملء حقل balance_period تلقائياً بناءً على الـ type
            // لتجنب التناقض، وبذلك لن يحتاج المستخدم لإدخاله يدوياً
            if ($leaveType->type === self::TYPE_YEARLY) {
                $leaveType->balance_period = self::BALANCE_PERIOD_YEARLY;
            } elseif (in_array($leaveType->type, [self::TYPE_MONTHLY, self::TYPE_WEEKLY])) {
                $leaveType->balance_period = self::BALANCE_PERIOD_MONTHLY;
            } else {
                // للأنواع الخاصة (Special)
                $leaveType->balance_period = $leaveType->balance_period ?? self::BALANCE_PERIOD_OTHER;
            }
        });
    }
}
