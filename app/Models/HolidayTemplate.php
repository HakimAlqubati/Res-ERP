<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class HolidayTemplate extends Model implements Auditable
{
    use HasFactory,
        SoftDeletes,
        \OwenIt\Auditing\Auditable;

    protected $table = 'hr_holiday_templates';

    // ─────────────────────────────────────────────────────────────
    // Holiday Type Constants
    // ─────────────────────────────────────────────────────────────
    public const TYPE_FIXED = 'fixed';
    public const TYPE_HIJRI = 'hijri';

    public const TYPES = [
        self::TYPE_FIXED,
        self::TYPE_HIJRI,
    ];

    // ─────────────────────────────────────────────────────────────
    // Holiday Status Constants
    // ─────────────────────────────────────────────────────────────
    public const STATUS_TENTATIVE = 'tentative';
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUSES = [
        self::STATUS_TENTATIVE,
        self::STATUS_CONFIRMED,
    ];

    protected $fillable = [
        'name',
        'type',
        'is_recurring',
        'day',
        'month',
        'year',
        'from_date',
        'to_date',
        'count_days',
        'status',
        'all_branches',
        'branch_ids',
        'active',
        'notes',
        'created_by',
        'updated_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $auditInclude = [
        'name',
        'type',
        'is_recurring',
        'day',
        'month',
        'year',
        'from_date',
        'to_date',
        'count_days',
        'status',
        'all_branches',
        'branch_ids',
        'active',
        'notes',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'day'          => 'integer',
        'month'        => 'integer',
        'year'         => 'integer',
        'from_date'    => 'date',
        'to_date'      => 'date',
        'count_days'   => 'integer',
        'all_branches' => 'boolean',
        'branch_ids'   => 'array',
        'active'       => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Lifecycle Hooks
    // ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });

        static::saving(function (self $model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }

            if ($model->all_branches) {
                $model->branch_ids = null;
            } elseif (is_array($model->branch_ids)) {
                $model->branch_ids = array_values(array_unique(array_map('intval', $model->branch_ids)));
            }

            if ($model->isFixed() && $model->month && $model->day && empty($model->from_date)) {
                $targetYear = (int) ($model->year ?: now()->year);
                $daysCount = max(1, (int) ($model->count_days ?: 1));
                $start = Carbon::createFromDate($targetYear, (int) $model->month, (int) $model->day)->startOfDay();

                $model->from_date = $start->toDateString();
                $model->to_date = $start->copy()->addDays($daysCount - 1)->toDateString();
            }

            if ($model->from_date && $model->to_date) {
                $from = Carbon::parse($model->from_date)->startOfDay();
                $to = Carbon::parse($model->to_date)->startOfDay();

                if ($to->lt($from)) {
                    $to = $from->copy();
                    $model->to_date = $to->toDateString();
                }

                $model->count_days = (int) $from->diffInDays($to) + 1;

                if (empty($model->year)) {
                    $model->year = (int) $from->year;
                }

                if ($model->isFixed()) {
                    $model->month ??= (int) $from->month;
                    $model->day ??= (int) $from->day;
                }
            }

            if ($model->isConfirmed() && empty($model->confirmed_at)) {
                $model->confirmed_at = now();
                $model->confirmed_by ??= auth()->id();
            }

            if ($model->isTentative()) {
                $model->confirmed_at = null;
                $model->confirmed_by = null;
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Type & Status Helpers (Single Source of Truth)
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_FIXED => 'Fixed Gregorian (Yearly)',
            self::TYPE_HIJRI => 'Variable Hijri (Lunar)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeColors(): array
    {
        return [
            self::TYPE_FIXED => 'info',
            self::TYPE_HIJRI => 'primary',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeIcons(): array
    {
        return [
            self::TYPE_FIXED => 'heroicon-m-calendar-days',
            self::TYPE_HIJRI => 'heroicon-m-moon',
        ];
    }

    public static function getTypeLabel(?string $type): string
    {
        return self::typeOptions()[$type] ?? (string) ($type ?? '');
    }

    public static function getTypeColor(?string $type): string
    {
        return self::typeColors()[$type] ?? 'gray';
    }

    public static function getTypeIcon(?string $type): string
    {
        return self::typeIcons()[$type] ?? 'heroicon-m-calendar';
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_TENTATIVE => 'Tentative',
            self::STATUS_CONFIRMED => 'Confirmed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusColors(): array
    {
        return [
            self::STATUS_TENTATIVE => 'warning',
            self::STATUS_CONFIRMED => 'success',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusIcons(): array
    {
        return [
            self::STATUS_TENTATIVE => 'heroicon-m-clock',
            self::STATUS_CONFIRMED => 'heroicon-m-check-badge',
        ];
    }

    public static function getStatusLabel(?string $status): string
    {
        return self::statusOptions()[$status] ?? (string) ($status ?? '');
    }

    public static function getStatusColor(?string $status): string
    {
        return self::statusColors()[$status] ?? 'gray';
    }

    public static function getStatusIcon(?string $status): string
    {
        return self::statusIcons()[$status] ?? 'heroicon-m-question-mark-circle';
    }

    /**
     * @return array<int, string>
     */
    public static function gregorianMonthOptions(): array
    {
        return [
            1  => '1 - January',
            2  => '2 - February',
            3  => '3 - March',
            4  => '4 - April',
            5  => '5 - May',
            6  => '6 - June',
            7  => '7 - July',
            8  => '8 - August',
            9  => '9 - September',
            10 => '10 - October',
            11 => '11 - November',
            12 => '12 - December',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function hijriMonthOptions(): array
    {
        return [
            1  => '1 - Muharram',
            2  => '2 - Safar',
            3  => '3 - Rabi\' al-Awwal',
            4  => '4 - Rabi\' al-Thani',
            5  => '5 - Jumada al-Ula',
            6  => '6 - Jumada al-Akhirah',
            7  => '7 - Rajab',
            8  => '8 - Sha\'ban',
            9  => '9 - Ramadan',
            10 => '10 - Shawwal',
            11 => '11 - Dhu al-Qi\'dah',
            12 => '12 - Dhu al-Hijjah',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function monthOptions(?string $type = null): array
    {
        return $type === self::TYPE_HIJRI
            ? self::hijriMonthOptions()
            : self::gregorianMonthOptions();
    }

    /**
     * @return array<int, int>
     */
    public static function dayOptions(?string $type = null): array
    {
        $maxDay = $type === self::TYPE_HIJRI ? 30 : 31;

        return array_combine(range(1, $maxDay), range(1, $maxDay));
    }

    /**
     * @return array<int, int>
     */
    public static function yearOptions(): array
    {
        $currentYear = (int) now()->year;
        $years = range($currentYear - 2, $currentYear + 5);

        return array_combine($years, $years);
    }

    // ─────────────────────────────────────────────────────────────
    // Instance State & Formatting Methods
    // ─────────────────────────────────────────────────────────────

    public function isFixed(): bool
    {
        return $this->type === self::TYPE_FIXED;
    }

    public function isHijri(): bool
    {
        return $this->type === self::TYPE_HIJRI;
    }

    public function isTentative(): bool
    {
        return $this->status === self::STATUS_TENTATIVE;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isApplicableToBranch(int $branchId): bool
    {
        if ($this->all_branches) {
            return true;
        }

        $branchIds = array_map('intval', $this->branch_ids ?? []);

        return in_array($branchId, $branchIds, true);
    }

    public function formattedDayMonth(): string
    {
        if (! $this->day || ! $this->month) {
            return '-';
        }

        $months = self::monthOptions($this->type);
        $monthLabel = $months[$this->month] ?? (string) $this->month;
        $monthName = trim(explode('-', $monthLabel, 2)[1] ?? $monthLabel);

        return "{$this->day} {$monthName}";
    }

    public function branchesSummary(): string
    {
        if ($this->all_branches) {
            return 'All Branches';
        }

        $count = count($this->branch_ids ?? []);

        return $count > 0 ? "{$count} Branch(es)" : 'No Branches';
    }

    // ─────────────────────────────────────────────────────────────
    // Query Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeTentative(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_TENTATIVE);
    }

    public function scopeFixed(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_FIXED);
    }

    public function scopeHijri(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_HIJRI);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where(function (Builder $q) use ($year): void {
            $q->where('year', $year)
                ->orWhere(function (Builder $sub): void {
                    $sub->where('type', self::TYPE_FIXED)
                        ->where('is_recurring', true);
                });
        });
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where(function (Builder $q) use ($branchId): void {
            $q->where('all_branches', true)
                ->orWhereJsonContains('branch_ids', $branchId)
                ->orWhereJsonContains('branch_ids', (string) $branchId);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
