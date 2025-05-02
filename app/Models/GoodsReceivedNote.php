<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class GoodsReceivedNote extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'grn_date',
        'purchase_invoice_id',
        'store_id',
        'grn_number',
        'notes',
        'is_approved',
        'is_cancelled',
        'created_by',
        'updated_by',
        'approved_by',
        'cancelled_by',
        'cancel_reason',
        'supplier_id',
        'status',
    ];

    protected $auditInclude = [
        'grn_date',
        'purchase_invoice_id',
        'store_id',
        'grn_number',
        'notes',
        'is_approved',
        'is_cancelled',
        'created_by',
        'updated_by',
        'approved_by',
        'cancelled_by',
        'cancel_reason',
        'supplier_id',
        'status',
    ];
    protected $appends = ['details_count'];
    const STATUS_CREATED   = 'created';
    const STATUS_APPROVED  = 'approved';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED  = 'rejected';

    protected $casts = [
        'grn_date' => 'date',
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
}
