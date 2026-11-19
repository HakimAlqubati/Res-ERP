<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class HolidayTemplate extends Model implements Auditable
{
    use HasFactory,
        SoftDeletes,
        \OwenIt\Auditing\Auditable;

    protected $table = 'hr_holiday_templates';

    // ─────────────────────────────────────────────────────────────
    // Calendar Type Constants
    // ─────────────────────────────────────────────────────────────
    public const TYPE_FIXED = 'fixed';
    public const TYPE_HIJRI = 'hijri';

    public const TYPES = [
        self::TYPE_FIXED,
        self::TYPE_HIJRI,
    ];

    protected $fillable = [
        'name',
        'type',
        'day',
        'month',
        'count_days',
        'all_branches',
        'branch_ids',
        'active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $auditInclude = [
        'name',
        'type',
        'day',
        'month',
        'count_days',
        'all_branches',
        'branch_ids',
        'active',
        'notes',
    ];

    protected $casts = [
        'day'          => 'integer',
        'month'        => 'integer',
        'count_days'   => 'integer',
        'all_branches' => 'boolean',
        'branch_ids'   => 'array',
        'active'       => 'boolean',
    ];

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
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Type & Calendar Helpers (Single Source of Truth)
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

    // ─────────────────────────────────────────────────────────────
    // Instance State & Formatting
    // ─────────────────────────────────────────────────────────────

    public function isFixed(): bool
    {
        return $this->type === self::TYPE_FIXED;
    }

    public function isHijri(): bool
    {
        return $this->type === self::TYPE_HIJRI;
    }

    public function isApplicableToBranch(int $branchId): bool
    {
        if ($this->all_branches) {
            return true;
        }

        return in_array($branchId, array_map('intval', $this->branch_ids ?? []), true);
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
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeFixed(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_FIXED);
    }

    public function scopeHijri(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_HIJRI);
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

    public function occurrences(): HasMany
    {
        return $this->hasMany(HolidayOccurrence::class, 'holiday_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
