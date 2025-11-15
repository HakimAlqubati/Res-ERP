<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Services\ProductCostingService;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Inventory\CanCancelPurchaseInvoice;


class PurchaseInvoice extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, CanCancelPurchaseInvoice;

    protected $fillable = [
        'date',
        'supplier_id',
        'description',
        'invoice_no',
        'store_id',
        'attachment',
        'cancelled',
        'cancel_reason',
        'payment_method_id',
        'created_by',
        'total_amount',
    ];
    protected $auditInclude = [
        'date',
        'supplier_id',
        'description',
        'invoice_no',
        'store_id',
        'attachment',
        'cancelled',
        'cancel_reason',
        'cancelled_by',
        'created_by',
        'total_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
    ];
    protected $appends = [
        'has_attachment',
        'has_description',
        'details_count',
        'has_grn',
        'has_inventory_transaction',
        'creator_name',
        'has_outbound_transactions',
    ];

    /**
     * Get the count of purchase invoice details.
     *
     * @return int
     */
    public function getDetailsCountAttribute()
    {
        return $this->purchaseInvoiceDetails()->count();
    }

    /**
     * Scope to filter purhchase invoices with details only.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithDetails($query)
    {
        return $query->withCount('purchaseInvoiceDetails') // Count unitPrices
            ->having('purchase_invoice_details_count', '>', 1); // Filter based on the count
    }

    public function purchaseInvoiceDetails()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchase_invoice_id');
    }
    public function details()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchase_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getHasAttachmentAttribute()
    {
        if (strlen($this->attachment) > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    public function getHasDescriptionAttribute()
    {
        return !empty($this->description) ? 1 : 0;
    }


    public function grn()
    {
        return $this->hasOne(GoodsReceivedNote::class, 'purchase_invoice_id');
    }
    public function getHasGrnAttribute(): bool
    {
        return $this->grn()->exists();
    }
    public static function autoInvoiceNo()
    {
        return (PurchaseInvoice::query()
            ->orderBy('id', 'desc')
            ->withTrashed()
            ->value('id') + 1 ?? 1);
    }
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->purchaseInvoiceDetails->sum('total_price');
    }

    public function getHasInventoryTransactionAttribute(): bool
    {
        // تحقق من الإدخالات المباشرة
        $hasDirectInventory = InventoryTransaction::where('transactionable_type', self::class)
            ->where('transactionable_id', $this->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->exists();

        if ($hasDirectInventory) {
            return true;
        }

        // إذا لا توجد إدخالات مباشرة، تحقق من GRN المرتبطة
        $grn = $this->grn;

        if ($grn) {
            return InventoryTransaction::where('transactionable_type', GoodsReceivedNote::class)
                ->where('transactionable_id', $grn->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->exists();
        }

        return false;
    }
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function getCreatorNameAttribute()
    {
        return $this->creator?->name ?? null;
    }

    public function hasOutboundTransactionsFromInbound(): bool
    {
        $inboundTransactionIds = InventoryTransaction::where('transactionable_type', self::class)
            ->where('transactionable_id', $this->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->pluck('id');

        return InventoryTransaction::whereIn('source_transaction_id', $inboundTransactionIds)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->exists();
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


    protected function hasOutboundTransactions(): Attribute
    {
        return Attribute::get(fn() => $this->hasOutboundTransactionsFromInbound());
    }
    public function handleCancellation($invoice, string $reason): array
    {
        return $this->cancelPurchaseInvoice($invoice, $reason);
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'transactionable');
    }
}
