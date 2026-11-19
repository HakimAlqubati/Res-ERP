<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class HolidayOccurrence extends Model implements Auditable
{
    use HasFactory,
        SoftDeletes,
        \OwenIt\Auditing\Auditable;

    protected $table = 'hr_holiday_occurrences';

    // ─────────────────────────────────────────────────────────────
    // Status Constants
    // ─────────────────────────────────────────────────────────────
    public const STATUS_TENTATIVE = 'tentative';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_TENTATIVE,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'holiday_template_id',
        'name',
        'type',
        'year',
        'hijri_year',
        'expected_from_date',
        'expected_to_date',
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
        'holiday_template_id',
        'name',
        'type',
        'year',
        'hijri_year',
        'expected_from_date',
        'expected_to_date',
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
        'holiday_template_id' => 'integer',
        'year'                => 'integer',
        'hijri_year'          => 'integer',
        'expected_from_date'  => 'date',
        'expected_to_date'    => 'date',
        'from_date'           => 'date',
        'to_date'             => 'date',
        'count_days'          => 'integer',
        'all_branches'        => 'boolean',
        'branch_ids'          => 'array',
        'active'              => 'boolean',
        'confirmed_at'        => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Status Helpers (Single Source of Truth)
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_TENTATIVE => 'Tentative (Expected)',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_CANCELLED => 'Cancelled',
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
            self::STATUS_CANCELLED => 'danger',
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
            self::STATUS_CANCELLED => 'heroicon-m-x-circle',
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
     * @return array<int, int>
     */
    public static function yearOptions(): array
    {
        $currentYear = (int) now()->year;
        $years = range($currentYear - 2, $currentYear + 5);

        return array_combine($years, $years);
    }

    // ─────────────────────────────────────────────────────────────
    // Instance State & Formatting
    // ─────────────────────────────────────────────────────────────

    public function isTentative(): bool
    {
        return $this->status === self::STATUS_TENTATIVE;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFixed(): bool
    {
        return $this->type === HolidayTemplate::TYPE_FIXED;
    }

    public function isHijri(): bool
    {
        return $this->type === HolidayTemplate::TYPE_HIJRI;
    }

    public function isApplicableToBranch(int $branchId): bool
    {
        if ($this->all_branches) {
            return true;
        }

        return in_array($branchId, array_map('intval', $this->branch_ids ?? []), true);
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

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeTentative(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_TENTATIVE);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeOverlappingRange(Builder $query, string $fromDate, string $toDate): Builder
    {
        return $query->whereDate('from_date', '<=', $toDate)
            ->whereDate('to_date', '>=', $fromDate);
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(HolidayTemplate::class, 'holiday_template_id');
    }

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
