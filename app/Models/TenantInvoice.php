<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class TenantInvoice extends Model
{
    use UsesLandlordConnection;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const DEFAULT_WARNING_DAYS = 10;

    private const TEMP_INVOICE_PREFIX = 'TMP-';

    protected $table = 'tenant_invoices';

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'amount',
        'invoice_date',
        'due_date',
        'status',
        'paid_at',
        'warning_days',
        'notes',
        'created_by',
    ];

    protected $attributes = [
        'status' => self::STATUS_UNPAID,
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'amount' => 'decimal:2',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'warning_days' => 'integer',
            'created_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            if (blank($invoice->status)) {
                $invoice->status = self::STATUS_UNPAID;
            }

            if ($invoice->warning_days === null) {
                $invoice->warning_days = self::getDefaultWarningDays();
            }

            if ($invoice->created_by === null && auth()->check()) {
                $invoice->created_by = auth()->id();
            }

            if (blank($invoice->invoice_number)) {
                $invoice->invoice_number = self::TEMP_INVOICE_PREFIX . Str::ulid()->toBase32();
            }
        });

        static::saving(function (self $invoice): void {
            if ($invoice->status === self::STATUS_PAID && $invoice->paid_at === null) {
                $invoice->paid_at = now();
            }

            if ($invoice->status !== self::STATUS_PAID) {
                $invoice->paid_at = null;
            }
        });

        static::created(function (self $invoice): void {
            if (str_starts_with((string) $invoice->invoice_number, self::TEMP_INVOICE_PREFIX)) {
                $year = $invoice->invoice_date?->format('Y') ?? now()->format('Y');

                $invoice->forceFill([
                    'invoice_number' => self::formatInvoiceNumber((int) $invoice->getKey(), $year),
                ])->saveQuietly();
            }
        });
    }

    public static function getDefaultWarningDays(): int
    {
        return CentralSetting::getDefaultInvoiceWarningDays();
    }

    /**
     * @return array<string, string>
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_UNPAID => __('Unpaid'),
            self::STATUS_PAID => __('Paid'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PAID => 'success',
            self::STATUS_UNPAID => 'warning',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    public static function formatInvoiceNumber(int $id, ?string $year = null): string
    {
        $year ??= now()->format('Y');

        return sprintf('INV-%s-%04d', $year, $id);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(CustomTenantModel::class, 'tenant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNPAID);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_UNPAID)
            ->whereDate('due_date', '<', today());
    }

    public function scopeInWarningWindow(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_UNPAID)
            ->whereRaw('due_date <= DATE_ADD(?, INTERVAL warning_days DAY)', [today()->toDateString()]);
    }

    public static function getMostUrgentWarningForTenant(int $tenantId): ?self
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->inWarningWindow()
            ->orderBy('due_date', 'asc')
            ->first();
    }

    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->isUnpaid()
            && $this->due_date !== null
            && $this->due_date->lt(today());
    }

    public function isInWarningWindow(): bool
    {
        if (! $this->isUnpaid() || $this->due_date === null) {
            return false;
        }

        $daysUntilDue = (int) today()->diffInDays($this->due_date->copy()->startOfDay(), false);

        return $daysUntilDue <= $this->warning_days;
    }

    public function getOverdueDays(): int
    {
        if (! $this->isOverdue() || $this->due_date === null) {
            return 0;
        }

        return (int) $this->due_date->copy()->startOfDay()->diffInDays(today());
    }

    public function getWarningBadgeDays(): int
    {
        return $this->remaining_days ?? 0;
    }

    public function isUrgentWarning(): bool
    {
        return $this->isOverdue() || $this->getWarningBadgeDays() <= 3;
    }

    public function getWarningTooltip(): string
    {
        if ($this->isOverdue()) {
            return __('lang.tenant_invoice_overdue_tooltip', [
                'days' => $this->getOverdueDays(),
                'invoice' => $this->invoice_number,
            ]);
        }

        $remainingDays = $this->remaining_days;

        if ($remainingDays === null) {
            return __('lang.tenant_invoice_due_today_tooltip', [
                'invoice' => $this->invoice_number,
            ]);
        }

        return __('lang.tenant_invoice_warning_tooltip', [
            'days' => $remainingDays,
            'invoice' => $this->invoice_number,
        ]);
    }

    /**
     * Number of days remaining until due date for unpaid future invoices.
     */
    protected function remainingDays(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if (! $this->isUnpaid() || $this->due_date === null) {
                    return null;
                }

                $dueDate = $this->due_date->copy()->startOfDay();
                $today = today();

                if (! $dueDate->gt($today)) {
                    return null;
                }

                return (int) $today->diffInDays($dueDate);
            },
        );
    }

    public function markAsPaid(CarbonInterface|string|null $paidAt = null, ?string $notes = null): self
    {
        $resolvedPaidAt = match (true) {
            $paidAt instanceof CarbonInterface => $paidAt,
            is_string($paidAt) && trim($paidAt) !== '' => Carbon::parse($paidAt),
            default => now(),
        };

        $this->status = self::STATUS_PAID;
        $this->paid_at = $resolvedPaidAt;

        if (is_string($notes) && trim($notes) !== '') {
            $trimmedNotes = trim($notes);
            $this->notes = filled($this->notes)
                ? trim((string) $this->notes) . PHP_EOL . $trimmedNotes
                : $trimmedNotes;
        }

        $this->save();

        return $this;
    }
}
