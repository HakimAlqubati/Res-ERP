<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use App\Traits\Inventory\CanCancelGoodsReceivedNote;

class GoodsReceivedNote extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, CanCancelGoodsReceivedNote;

    protected $fillable = [
        'grn_date',
        'purchase_invoice_id',
        'store_id',
        'grn_number',
        'notes',
        'created_by',
        'updated_by',
        'approved_by',
        'cancelled_by',
        'cancel_reason',
        'supplier_id',
        'status',
        'is_purchase_invoice_created',
        'approve_date',
        'cancelled',
    ];

    protected $auditInclude = [
        'grn_date',
        'purchase_invoice_id',
        'store_id',
        'grn_number',
        'notes',
        'created_by',
        'updated_by',
        'approved_by',
        'cancelled_by',
        'cancel_reason',
        'supplier_id',
        'status',
        'is_purchase_invoice_created',
        'approve_date',
    ];
    protected $appends = [
        'details_count',
        'has_inventory_transaction',
        'belongs_to_purchase_invoice',
        'has_outbound_transactions',
    ];
    const STATUS_CREATED   = 'created';
    const STATUS_APPROVED  = 'approved';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED  = 'rejected';

    protected $casts = [
        'grn_date' => 'date',
        'approve_date' => 'datetime',
    ];

    // ✅ العلاقات

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function getDetailsCountAttribute()
    {
        return $this->grnDetails()->count();
    }

    public function getBelongsToPurchaseInvoiceAttribute(): bool
    {
        return !is_null($this->purchase_invoice_id);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // العلاقات الاختيارية للمستخدمين المسؤولين
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // في حال إضافة جدول التفاصيل لاحقًا
    public function grnDetails()
    {
        return $this->hasMany(GoodsReceivedNoteDetail::class, 'grn_id');
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'transactionable');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // ✅ دالة static ترجع خيارات الحالة
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_CREATED   => 'Created',
            self::STATUS_APPROVED  => 'Approved',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REJECTED  => 'Rejected',
        ];
    }

    // ✅ Scope للفلترة حسب الحالة
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ✅ Accessor لعرض الحالة بشكل مقروء
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? ucfirst($this->status);
    }

    protected static function booted()
    {
        static::updated(function ($grn) {
            if (
                $grn->isDirty('status') &&
                $grn->status === self::STATUS_APPROVED
                &&
                settingWithDefault('affect_inventory_from_grn_only', false)
            ) {
                foreach ($grn->grnDetails as $detail) {
                    $notes = 'GRN with id ' . $grn->id;
                    if ($grn->store?->name) {
                        $notes .= ' in (' . $grn->store->name . ')';
                    }

                    InventoryTransaction::create([
                        'product_id' => $detail->product_id,
                        'movement_type' => InventoryTransaction::MOVEMENT_IN,
                        'quantity' => $detail->quantity,
                        'package_size' => $detail->package_size,
                        'price' => getUnitPrice($detail->product_id, $detail->unit_id),
                        'movement_date' => $grn->grn_date ?? now(),
                        'unit_id' => $detail->unit_id,
                        'store_id' => $grn->store_id,
                        'notes' => $notes,
                        'transaction_date' => $grn->grn_date ?? now(),
                        'transactionable_id' => $grn->id,
                        'transactionable_type' => self::class,
                    ]);
                }
            }
        });
    }

    public function getHasInventoryTransactionAttribute(): bool
    {
        return InventoryTransaction::where('transactionable_type', self::class)
            ->where('transactionable_id', $this->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->exists();;
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function getHasOutboundTransactionsAttribute(): bool
    {
        $inboundTransactionIds = $this->inventoryTransactions()
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->pluck('id');

        return InventoryTransaction::whereIn('source_transaction_id', $inboundTransactionIds)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->exists();
    }

    public function handleCancellation(GoodsReceivedNote $grn, string $reason): array
    {
        return $this->cancelGoodsReceivedNote($grn, $reason);
    }

    public function getTotalAmountAttribute(): float
    {
        return max(0.0, (float) $this->grnDetails->sum(function ($detail) {
            if (!$detail->product?->unitPrices) {
                dd($detail->product);
            }
            $priceFromUnit = optional(
                $detail->product?->unitPrices
                    // لو تبي تقييد الأسعار بالـ scope المناسب، استعمل فلترة هنا إن احتجت
                    ->firstWhere('unit_id', $detail->unit_id)
            )->price;

            $price = $priceFromUnit ?? ($detail->price ?? 0);
            return (float) $detail->quantity * (float) $price;
        }));
    }
}
