<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\AdvanceWageObserver;

#[ObservedBy([AdvanceWageObserver::class])]

class AdvanceWage extends Model
{
    use SoftDeletes;

    protected $table = 'hr_advance_wages';

    // --- حالات الأجر المقدم ---
    const STATUS_PENDING   = 'pending';
    const STATUS_SETTLED   = 'settled';
    const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'date',
        'year',
        'month',
        'amount',
        'payment_method',
        'bank_account_number',
        'transaction_number',
        'status',
        'reason',
        'notes',
        'settled_payroll_id',
        'settled_at',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'date'        => 'date:Y-m-d',
        'year'        => 'integer',
        'month'       => 'integer',
        'settled_at'  => 'datetime',
        'approved_at' => 'datetime',
    ];

    // =========================================================
    // العلاقات
    // =========================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withoutGlobalScopes();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function settledPayroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'settled_payroll_id');
    }

    // =========================================================
    // Scopes
    // =========================================================

    /**
     * الأجور المقدمة بحالة pending (لم تُسوَّى بعد)
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * الأجور المقدمة بحالة settled
     */
    public function scopeSettled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SETTLED);
    }

    /**
     * تصفية حسب الموظف
     */
    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * تصفية حسب الفترة (سنة + شهر)
     */
    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('year', $year)->where('month', $month);
    }

    // =========================================================
    // الدوال المساعدة
    // =========================================================

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING   => __('Pending'),
            self::STATUS_SETTLED   => __('Settled'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_METHOD_CASH          => __('lang.cash'),
            self::PAYMENT_METHOD_BANK_TRANSFER  => __('lang.bank_transfer'),
        ];
    }

    /**
     * هل يمكن تسويته؟ (pending فقط)
     */
    public function canBeSettled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * هل يمكن إلغاؤه؟ (pending فقط)
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * تسوية الأجر المقدم وربطه بالـ Payroll
     */
    public function markAsSettled(int $payrollId): bool
    {
        return $this->update([
            'status'             => self::STATUS_SETTLED,
            'settled_payroll_id' => $payrollId,
            'settled_at'         => now(),
        ]);
    }

    /**
     * إلغاء الأجر المقدم
     */
    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * إجمالي الأجور المقدمة المعلقة لموظف في شهر معين
     */
    public static function pendingTotalFor(int $employeeId, int $year, int $month): float
    {
        return (float) static::forEmployee($employeeId)
            ->forPeriod($year, $month)
            ->pending()
            ->sum('amount');
    }


}
