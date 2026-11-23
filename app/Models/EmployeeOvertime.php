<?php

namespace App\Models;

use App\Observers\EmployeeOvertimeObserver;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Scopes\BranchScope;
use App\Traits\Scopes\StatusScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy([EmployeeOvertimeObserver::class])]
class EmployeeOvertime extends Model implements Auditable
{
    use HasFactory,
        SoftDeletes,
        \OwenIt\Auditing\Auditable,
        BranchScope,
        StatusScope;
    protected $table = 'hr_employee_overtime';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    // Fillable fields for mass assignment
    protected $fillable = [
        'employee_id',
        'date',
        'period_id',
        'start_time',
        'end_time',
        'hours',
        'rate',
        'reason',
        'status',
        'approved_by',
        'notes',
        'created_by',
        'updated_by',
        'branch_id',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'type',
    ];
    protected $auditInclude = [
        'employee_id',
        'date',
        'period_id',
        'start_time',
        'end_time',
        'hours',
        'rate',
        'reason',
        'status',
        'approved_by',
        'notes',
        'created_by',
        'updated_by',
        'branch_id',
        'approved_at',
        'type',
    ];

    // Enum values for the 'type' field
    public const TYPE_BASED_ON_MONTH = 'based_on_month';
    public const TYPE_BASED_ON_DAY = 'based_on_day';

    // Array of all possible types
    public const TYPES = [
        self::TYPE_BASED_ON_MONTH,
        self::TYPE_BASED_ON_DAY,
    ];

    public static function getTypes()
    {
        return [
            EmployeeOvertime::TYPE_BASED_ON_DAY => 'Hourly',
            EmployeeOvertime::TYPE_BASED_ON_MONTH => 'One-Day',
        ];
    }
    // Relationships
    // Relationship with the Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function workPeriod()
    {
        return $this->belongsTo(WorkPeriod::class, 'period_id');
    }

    /**
     * Intelligently resolve period_id for a daily/hourly overtime entry when not explicitly provided.
     * Supports 24h circular distance for cross-midnight shifts and preserves null for NO_SHIFT attendances.
     */
    public static function resolvePeriodId(int $employeeId, string $date, ?string $startTime = null): ?int
    {
        if ($employeeId <= 0 || empty($date)) {
            return null;
        }

        $dateStr = substr(trim($date), 0, 10);

        $timeToMinutes = static function (?string $timeStr): ?int {
            if (empty($timeStr)) {
                return null;
            }
            $parts = explode(':', trim($timeStr));
            if (count($parts) < 2) {
                return null;
            }
            return ((int) $parts[0] * 60) + (int) $parts[1];
        };

        $circularDistance = static function (int $m1, int $m2): int {
            $diff = abs($m1 - $m2);
            return min($diff, 1440 - $diff);
        };

        $attendances = Attendance::query()
            ->where('employee_id', $employeeId)
            ->where('check_date', $dateStr)
            ->where('accepted', 1)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get(['id', 'period_id', 'check_time', 'check_type', 'status']);

        $checkIns = $attendances->where('check_type', Attendance::CHECKTYPE_CHECKIN)->values();
        $noShiftValue = \App\Modules\HR\Attendance\Enums\AttendanceStatus::NO_SHIFT->value;

        if (! empty($startTime)) {
            $targetHm = substr(trim($startTime), 0, 5);
            $targetMinutes = $timeToMinutes($startTime);

            if ($checkIns->isNotEmpty()) {
                // 1. Exact HH:MM match on check-in
                $exact = $checkIns->first(fn ($att) => substr((string) $att->check_time, 0, 5) === $targetHm);
                if ($exact) {
                    if ($exact->period_id === null || $exact->status === $noShiftValue) {
                        return null;
                    }
                    return (int) $exact->period_id;
                }

                // 2. Closest check-in by circular 24-hour time distance
                if ($targetMinutes !== null) {
                    $closest = $checkIns->sortBy(function ($att) use ($timeToMinutes, $circularDistance, $targetMinutes) {
                        $attMin = $timeToMinutes($att->check_time);
                        return $attMin !== null ? $circularDistance($attMin, $targetMinutes) : 9999;
                    })->first();

                    if ($closest) {
                        if ($closest->period_id === null || $closest->status === $noShiftValue) {
                            return null;
                        }
                        return (int) $closest->period_id;
                    }
                }
            }

            // 3. Fallback to employee's active period histories on that date if no check-in records exist
            if ($targetMinutes !== null) {
                $histories = EmployeePeriodHistory::query()
                    ->with('workPeriod:id,start_at')
                    ->where('employee_id', $employeeId)
                    ->where('active', 1)
                    ->where(function ($q) use ($dateStr) {
                        $q->whereNull('start_date')->orWhere('start_date', '<=', $dateStr);
                    })
                    ->where(function ($q) use ($dateStr) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $dateStr);
                    })
                    ->get()
                    ->unique('period_id')
                    ->values();

                if ($histories->isNotEmpty()) {
                    $bestHistory = $histories->sortBy(function ($h) use ($timeToMinutes, $circularDistance, $targetMinutes) {
                        $pStart = $h->start_time ?? $h->workPeriod?->start_at;
                        $pMin = $timeToMinutes($pStart);
                        return $pMin !== null ? $circularDistance($pMin, $targetMinutes) : 9999;
                    })->first();

                    return $bestHistory ? (int) $bestHistory->period_id : null;
                }
            }

            return null;
        }

        // 4. When start_time is empty (e.g. BatchQuickAdd): resolve only if employee has a single unambiguous shift
        if ($attendances->isNotEmpty()) {
            $hasNoShift = $attendances->contains(fn ($a) => $a->period_id === null || $a->status === $noShiftValue);
            if ($hasNoShift) {
                return null;
            }
            $distinctPeriods = $attendances->pluck('period_id')->filter()->unique()->values();
            if ($distinctPeriods->count() === 1) {
                return (int) $distinctPeriods->first();
            }
            return null;
        }

        $distinctAssignedPeriods = EmployeePeriodHistory::query()
            ->where('employee_id', $employeeId)
            ->where('active', 1)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $dateStr);
            })
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $dateStr);
            })
            ->pluck('period_id')
            ->filter()
            ->unique()
            ->values();

        if ($distinctAssignedPeriods->count() === 1) {
            return (int) $distinctAssignedPeriods->first();
        }

        return null;
    }

    // Relationship with the User model (for approval)
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Relationship with the User model (for rejection)
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Relationship with the User model (who created the overtime entry)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with the User model (who updated the overtime entry)
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }



    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
        // Branch scope logic moved to ApplyBranchScopes middleware
        // to avoid relationship issues during model boot cycle.
        // See: app/Http/Middleware/ApplyBranchScopes.php
    }

    public function scopeDay($query)
    {
        return $query->where('type', static::TYPE_BASED_ON_DAY);
    }

    public function scopeMonth($query)
    {
        return $query->where('type', static::TYPE_BASED_ON_MONTH);
    }

    /**
     * Accessor for the 'type_value' attribute.
     */
    protected function typeValue(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->type) {
            self::TYPE_BASED_ON_DAY => 'Hourly',
            self::TYPE_BASED_ON_MONTH => 'Daily',
            default => 'Unknown',
        })->shouldCache();
    }

    /**
     * Format decimal hours (e.g. 1.50) into 'H:i' format (e.g. 1:30).
     */
    public static function formatHours(float|int|string|null $hours): ?string
    {
        if ($hours === null || $hours === '') {
            return null;
        }

        $hours = (float) $hours;
        $isNegative = $hours < 0;
        $absHours = abs($hours);

        $h = (int) $absHours;
        $m = (int) round(($absHours - $h) * 60);

        if ($m >= 60) {
            $h += 1;
            $m = 0;
        }

        return ($isNegative ? '-' : '') . sprintf('%d:%02d', $h, $m);
    }

    /**
     * Accessor for the 'hours_formatted' attribute.
     */
    protected function hoursFormatted(): Attribute
    {
        return Attribute::get(fn (): ?string => static::formatHours($this->hours))->shouldCache();
    }
}
