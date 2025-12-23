<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAdvanceInstallment extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_advance_installments';

    protected $fillable = [
        'employee_id',
        'application_id',
        'advance_request_id',
        'installment_amount',
        'original_amount',
        'due_date',
        'year',
        'month',
        'is_paid',
        'paid_date',
        'paid_by',
        'payment_method',
        'sequence',
        'status',
        'paid_payroll_id',
        'notes',
        'skipped_reason',
        'cancelled_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'installment_amount' => 'decimal:2',
        'original_amount'    => 'decimal:2',
        'due_date'           => 'date',
        'paid_date'          => 'datetime',
        'cancelled_at'       => 'datetime',
        'is_paid'            => 'boolean',
        'year'               => 'integer',
        'month'              => 'integer',
    ];


    // ✅ ثوابت الحالات
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PAID      = 'paid';
    public const STATUS_SKIPPED   = 'skipped';
    public const STATUS_CANCELLED = 'cancelled';

    // ✅ ثوابت طرق الدفع
    public const PAYMENT_METHOD_PAYROLL       = 'payroll';
    public const PAYMENT_METHOD_CASH          = 'cash';
    public const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';

    public static array $statuses = [
        self::STATUS_SCHEDULED,
        self::STATUS_PAID,
        self::STATUS_SKIPPED,
        self::STATUS_CANCELLED,
    ];

    public static array $paymentMethods = [
        self::PAYMENT_METHOD_PAYROLL       => 'Payroll Deduction',
        self::PAYMENT_METHOD_CASH          => 'Cash Payment',
        self::PAYMENT_METHOD_BANK_TRANSFER => 'Bank Transfer',
    ];

    // ===================== Relationships =====================

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function application()
    {
        return $this->belongsTo(EmployeeApplicationV2::class, 'application_id');
    }

    public function advanceRequest()
    {
        return $this->belongsTo(AdvanceRequest::class, 'advance_request_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'paid_payroll_id');
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // ===================== Scopes =====================

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeDueInMonth($query, int $year, int $month)
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = Carbon::create($year, $month, 1)->endOfMonth();
        return $query->whereBetween('due_date', [$start, $end]);
    }

    public function scopeForYearMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_paid', false)
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    // ===================== Helper Methods =====================

    /**
     * تعليم القسط كمسدّد
     */
    public function markPaid(
        ?int $payrollId = null,
        ?int $paidById = null,
        string $paymentMethod = self::PAYMENT_METHOD_PAYROLL,
        ?\DateTimeInterface $when = null
    ): void {
        $this->is_paid        = true;
        $this->status         = self::STATUS_PAID;
        $this->paid_payroll_id = $payrollId;
        $this->paid_by        = $paidById ?? auth()->id();
        $this->payment_method = $paymentMethod;
        $this->paid_date      = $when ? Carbon::parse($when) : now();
        $this->save();
    }

    /**
     * تعليم القسط كمتخطّى
     */
    public function markSkipped(?string $reason = null): void
    {
        $this->status         = self::STATUS_SKIPPED;
        $this->skipped_reason = $reason;
        $this->save();
    }

    /**
     * إلغاء القسط
     */
    public function markCancelled(?string $reason = null, ?int $cancelledById = null): void
    {
        $this->status           = self::STATUS_CANCELLED;
        $this->cancelled_reason = $reason;
        $this->cancelled_by     = $cancelledById ?? auth()->id();
        $this->cancelled_at     = now();
        $this->save();
    }

    /**
     * هل القسط متأخر؟
     */
    public function isOverdue(): bool
    {
        return !$this->is_paid && $this->due_date && $this->due_date->lt(now()->startOfDay());
    }

    /**
     * الأيام المتبقية حتى الاستحقاق
     */
    public function daysUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->due_date, false);
    }

    // ===================== Boot Method =====================

    protected static function booted(): void
    {
        // Auto-populate year and month from due_date when creating
        static::creating(function (self $installment) {
            if ($installment->due_date && !$installment->year) {
                $installment->year = Carbon::parse($installment->due_date)->year;
            }
            if ($installment->due_date && !$installment->month) {
                $installment->month = Carbon::parse($installment->due_date)->month;
            }
        });
    }
}
