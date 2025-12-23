<?php

namespace App\Models;

use Throwable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class AdvanceRequest extends Model
{
    use HasFactory;

    protected $table = 'hr_advance_requests';

    // ===================== Payment Status Constants =====================

    public const PAYMENT_STATUS_NOT_STARTED    = 'not_started';
    public const PAYMENT_STATUS_PARTIALLY_PAID = 'partially_paid';
    public const PAYMENT_STATUS_FULLY_PAID     = 'fully_paid';
    public const PAYMENT_STATUS_OVERDUE        = 'overdue';

    /**
     * Payment status labels for display
     */
    public static array $paymentStatuses = [
        self::PAYMENT_STATUS_NOT_STARTED    => 'Not Started',
        self::PAYMENT_STATUS_PARTIALLY_PAID => 'Partially Paid',
        self::PAYMENT_STATUS_FULLY_PAID     => 'Fully Paid',
        self::PAYMENT_STATUS_OVERDUE        => 'Overdue',
    ];

    /**
     * Get translated payment statuses for dropdowns
     */
    public static function getPaymentStatusOptions(): array
    {
        return [
            self::PAYMENT_STATUS_NOT_STARTED    => __('lang.not_started'),
            self::PAYMENT_STATUS_PARTIALLY_PAID => __('lang.partially_paid'),
            self::PAYMENT_STATUS_FULLY_PAID     => __('lang.fully_paid'),
            self::PAYMENT_STATUS_OVERDUE        => __('lang.overdue'),
        ];
    }

    // ===================== Fillable =====================

    protected $fillable = [
        'application_id',
        'application_type_id',
        'application_type_name',
        'employee_id',
        'advance_amount',
        'monthly_deduction_amount',
        'deduction_ends_at',
        'number_of_months_of_deduction',
        'date',
        'deduction_starts_from',
        'reason',
        'code',
        'status',
        'remaining_total',
        'paid_installments'
    ];

    protected $appends = ['payment_status'];

    // ===================== Relationships =====================

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function installments()
    {
        return $this->hasMany(EmployeeAdvanceInstallment::class, 'advance_request_id');
    }

    public function application()
    {
        return $this->belongsTo(EmployeeApplicationV2::class, 'application_id');
    }

    // ===================== Accessors =====================

    /**
     * Get the computed payment status based on paid_installments and remaining_total
     */
    public function getPaymentStatusAttribute(): string
    {
        // Fully paid
        if ($this->remaining_total <= 0) {
            return self::PAYMENT_STATUS_FULLY_PAID;
        }

        // Check if overdue (has unpaid installments past due date)
        $hasOverdue = $this->installments()
            ->where('is_paid', false)
            ->where('due_date', '<', now()->toDateString())
            ->exists();

        if ($hasOverdue) {
            return self::PAYMENT_STATUS_OVERDUE;
        }

        // Partially paid
        if ($this->paid_installments > 0) {
            return self::PAYMENT_STATUS_PARTIALLY_PAID;
        }

        // Not started
        return self::PAYMENT_STATUS_NOT_STARTED;
    }

    /**
     * Get the translated payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_FULLY_PAID     => __('lang.fully_paid'),
            self::PAYMENT_STATUS_PARTIALLY_PAID => __('lang.partially_paid'),
            self::PAYMENT_STATUS_OVERDUE        => __('lang.overdue'),
            default                              => __('lang.not_started'),
        };
    }

    /**
     * Get payment status color for badges
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_FULLY_PAID     => 'success',
            self::PAYMENT_STATUS_PARTIALLY_PAID => 'warning',
            self::PAYMENT_STATUS_OVERDUE        => 'danger',
            default                              => 'gray',
        };
    }

    // ===================== Scopes =====================

    /**
     * Scope for fully paid advances
     */
    public function scopeFullyPaid(Builder $query): Builder
    {
        return $query->where('remaining_total', '<=', 0);
    }

    /**
     * Scope for partially paid advances
     */
    public function scopePartiallyPaid(Builder $query): Builder
    {
        return $query->where('remaining_total', '>', 0)
            ->where('paid_installments', '>', 0);
    }

    /**
     * Scope for not started advances
     */
    public function scopeNotStarted(Builder $query): Builder
    {
        return $query->where('paid_installments', 0)
            ->orWhereNull('paid_installments');
    }

    /**
     * Scope for overdue advances (has unpaid installments past due date)
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('remaining_total', '>', 0)
            ->whereHas('installments', function ($q) {
                $q->where('is_paid', false)
                    ->where('due_date', '<', now()->toDateString());
            });
    }

    /**
     * Scope for filtering by payment status
     */
    public function scopePaymentStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            self::PAYMENT_STATUS_FULLY_PAID     => $query->fullyPaid(),
            self::PAYMENT_STATUS_PARTIALLY_PAID => $query->partiallyPaid(),
            self::PAYMENT_STATUS_NOT_STARTED    => $query->notStarted(),
            self::PAYMENT_STATUS_OVERDUE        => $query->overdue(),
            default                              => $query,
        };
    }

    // ===================== Static Methods =====================

    public static function createInstallments(
        $employeeId,
        $totalAmount,
        $numberOfMonths,
        string|\DateTimeInterface $startMonth,
        $applicationId,
        ?int $advanceRequestId = null
    ) {
        if ($numberOfMonths <= 0 || $totalAmount <= 0) return;

        DB::transaction(function () use ($employeeId, $totalAmount, $numberOfMonths, $startMonth, $applicationId, $advanceRequestId) {
            // prevent duplicates for same application
            if (EmployeeAdvanceInstallment::where('application_id', $applicationId)->exists()) {
                return;
            }

            // Get the advance_request_id if not provided
            if (!$advanceRequestId) {
                $advanceRequest = static::where('application_id', $applicationId)->first();
                $advanceRequestId = $advanceRequest?->id;
            }

            $base = floor(($totalAmount / $numberOfMonths) * 100) / 100;   // 2-dec
            $acc  = round($base * ($numberOfMonths - 1), 2);
            $last = round($totalAmount - $acc, 2);

            $cursor = Carbon::parse($startMonth)->startOfMonth();

            for ($i = 0; $i < $numberOfMonths; $i++) {
                $slice = ($i === $numberOfMonths - 1) ? $last : $base;
                $dueDate = (clone $cursor)->endOfMonth();

                EmployeeAdvanceInstallment::create([
                    'employee_id'        => $employeeId,
                    'application_id'     => $applicationId,
                    'advance_request_id' => $advanceRequestId,
                    'sequence'           => $i + 1,
                    'installment_amount' => $slice,
                    'original_amount'    => $slice,
                    'due_date'           => $dueDate->toDateString(),
                    'year'               => $dueDate->year,
                    'month'              => $dueDate->month,
                    'is_paid'            => false,
                    'status'             => EmployeeAdvanceInstallment::STATUS_SCHEDULED,
                ]);

                $cursor->addMonth();
            }
        });
    }

    // ===================== Boot Method =====================

    protected static function booted(): void
    {
        static::creating(function (AdvanceRequest $model) {
            if (empty($model->code)) {
                $model->code = static::nextCode();
            }

            if (is_null($model->remaining_total)) {
                $model->remaining_total = (float) $model->advance_amount;
            }
            if (is_null($model->paid_installments)) {
                $model->paid_installments = 0;
            }
        });
    }

    // ===================== Code Generation =====================

    public static function nextCode(): string
    {
        $prefix = 'ADV-' . now()->format('Ym') . '-';
        $last = DB::table('hr_advance_requests')
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, LENGTH(?) + 1) AS UNSIGNED)) as max_seq", [$prefix])
            ->value('max_seq');

        $seq = (int) $last + 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
