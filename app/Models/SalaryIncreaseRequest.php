<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\SalaryIncreaseRequestObserver;
use App\Traits\Scopes\BranchScope;
use App\Traits\Scopes\StatusScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy([SalaryIncreaseRequestObserver::class])]
class SalaryIncreaseRequest extends Model implements Auditable
{
    use HasFactory,
        SoftDeletes,
        \OwenIt\Auditing\Auditable,
        BranchScope,
        StatusScope;

    protected $table = 'hr_salary_increase_requests';

    // ─────────────────────────────────────────────────────────────
    // Status Constants
    // ─────────────────────────────────────────────────────────────
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    // ─────────────────────────────────────────────────────────────
    // Increase Calculation Type Constants
    // ─────────────────────────────────────────────────────────────
    public const INCREASE_TYPE_FIXED      = 'fixed';
    public const INCREASE_TYPE_PERCENTAGE = 'percentage';
    public const INCREASE_TYPE_NEW_SALARY = 'new_salary';

    public const INCREASE_TYPES = [
        self::INCREASE_TYPE_FIXED,
        self::INCREASE_TYPE_PERCENTAGE,
        self::INCREASE_TYPE_NEW_SALARY,
    ];

    protected $fillable = [
        'employee_id',
        'branch_id',
        'old_salary',
        'increase_type',
        'increase_amount',
        'increase_percentage',
        'new_salary',
        'effective_date',
        'notes',
        'status',
        'rejection_reason',
        'created_by',
        'updated_by',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
    ];

    protected $auditInclude = [
        'employee_id',
        'branch_id',
        'old_salary',
        'increase_type',
        'increase_amount',
        'increase_percentage',
        'new_salary',
        'effective_date',
        'notes',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'old_salary'          => 'decimal:2',
        'increase_amount'     => 'decimal:2',
        'increase_percentage' => 'decimal:2',
        'new_salary'          => 'decimal:2',
        'effective_date'      => 'date',
        'approved_at'         => 'datetime',
        'rejected_at'         => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Status & Type Helpers (Single Source of Truth)
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING  => __('lang.pending') !== 'lang.pending' ? __('lang.pending') : 'Pending',
            self::STATUS_APPROVED => __('lang.approved') !== 'lang.approved' ? __('lang.approved') : 'Approved',
            self::STATUS_REJECTED => __('lang.rejected') !== 'lang.rejected' ? __('lang.rejected') : 'Rejected',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusColors(): array
    {
        return [
            self::STATUS_PENDING  => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusIcons(): array
    {
        return [
            self::STATUS_PENDING  => 'heroicon-m-clock',
            self::STATUS_APPROVED => 'heroicon-m-check-badge',
            self::STATUS_REJECTED => 'heroicon-m-x-circle',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function increaseTypeOptions(): array
    {
        return [
            self::INCREASE_TYPE_FIXED      => 'Fixed Amount',
            self::INCREASE_TYPE_PERCENTAGE => 'Percentage (%)',
            self::INCREASE_TYPE_NEW_SALARY => 'Target New Salary',
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

    public function statusLabel(): string
    {
        return self::getStatusLabel($this->status);
    }

    public function statusColor(): string
    {
        return self::getStatusColor($this->status);
    }

    public function statusIcon(): string
    {
        return self::getStatusIcon($this->status);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        return in_array($targetStatus, [self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function salaryLog(): HasOne
    {
        return $this->hasOne(EmployeeSalaryLog::class, 'salary_increase_request_id');
    }
}
