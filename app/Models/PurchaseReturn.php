<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PurchaseReturn extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'return_no',
        'return_date',
        'purchase_invoice_id',
        'supplier_id',
        'store_id',
        'payment_method_id',
        'status',
        'total_amount',
        'reason',
        'notes',
        'attachment',
        'created_by',
        'approved_by',
        'approved_at',
        'cancelled',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $auditInclude = [
        'return_no',
        'return_date',
        'purchase_invoice_id',
        'supplier_id',
        'store_id',
        'status',
        'total_amount',
        'cancelled',
        'cancel_reason',
    ];

    protected $casts = [
        'return_date'  => 'date',
        'approved_at'  => 'datetime',
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:4',
        'cancelled'    => 'boolean',
    ];

    protected $appends = [
        'details_count',
        'is_approved',
        'is_draft',
        'creator_name',
    ];

    // ================= Relationships ================= //

    public function details()
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'purchase_return_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'transactionable');
    }

    public function financialTransactions()
    {
        return $this->morphMany(FinancialTransaction::class, 'reference');
    }

    // ================= Scopes & Accessors ================= //

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function getDetailsCountAttribute(): int
    {
        return $this->details()->count();
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getCreatorNameAttribute(): ?string
    {
        return $this->creator?->name;
    }

    public static function autoReturnNo(): string
    {
        $nextId = (self::withTrashed()->max('id') ?? 0) + 1;
        return 'PR-' . date('Ymd') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_APPROVED  => 'Approved',
            self::STATUS_REJECTED  => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (empty($model->return_no)) {
                $model->return_no = self::autoReturnNo();
            }
        });
    }
}
