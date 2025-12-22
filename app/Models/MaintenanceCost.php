<?php

namespace App\Models;

use App\Traits\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * نموذج تكاليف الصيانة
 * 
 * يدعم علاقة polymorphic مع:
 * - ServiceRequest (طلبات الصيانة)
 * - Equipment (المعدات)
 * 
 * يتم إنشاء معاملة مالية تلقائياً عند إنشاء تكلفة جديدة
 */
class MaintenanceCost extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, BranchScope;

    protected $table = 'hr_maintenance_costs';

    // ==================== Cost Types ====================
    const TYPE_REPAIR = 'repair';      // تكلفة الإصلاح
    const TYPE_PARTS = 'parts';        // قطع الغيار
    const TYPE_LABOR = 'labor';        // تكلفة العمالة
    const TYPE_PURCHASE = 'purchase';  // شراء معدات
    const TYPE_OTHER = 'other';        // أخرى

    const TYPE_LABELS = [
        self::TYPE_REPAIR => 'Repair Cost',
        self::TYPE_PARTS => 'Parts Cost',
        self::TYPE_LABOR => 'Labor Cost',
        self::TYPE_PURCHASE => 'Equipment Purchase',
        self::TYPE_OTHER => 'Other',
    ];

    // ==================== Fillable Fields ====================
    protected $fillable = [
        'amount',
        'description',
        'cost_type',
        'costable_type',
        'costable_id',
        'branch_id',
        'created_by',
        'cost_date',
        'synced_to_financial',
    ];

    protected $auditInclude = [
        'amount',
        'description',
        'cost_type',
        'costable_type',
        'costable_id',
        'branch_id',
        'cost_date',
        'synced_to_financial',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cost_date' => 'date',
        'synced_to_financial' => 'boolean',
    ];

    // ==================== Relationships ====================

    /**
     * العلاقة Polymorphic - يشير إلى ServiceRequest أو Equipment
     */
    public function costable()
    {
        return $this->morphTo();
    }

    /**
     * العلاقة مع الفرع
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * العلاقة مع منشئ السجل
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== Scopes ====================

    /**
     * فلترة حسب نوع التكلفة
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('cost_type', $type);
    }

    /**
     * فلترة التكاليف غير المزامنة
     */
    public function scopeNotSynced($query)
    {
        return $query->where('synced_to_financial', false);
    }

    /**
     * فلترة التكاليف المزامنة
     */
    public function scopeSynced($query)
    {
        return $query->where('synced_to_financial', true);
    }

    // ==================== Helpers ====================

    /**
     * الحصول على تسمية نوع التكلفة
     */
    public function getCostTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->cost_type] ?? $this->cost_type;
    }

    /**
     * التحقق مما إذا كانت التكلفة متعلقة بالشراء
     */
    public function isPurchase(): bool
    {
        return $this->cost_type === self::TYPE_PURCHASE;
    }

    /**
     * التحقق مما إذا كانت التكلفة متعلقة بالصيانة
     */
    public function isMaintenanceRelated(): bool
    {
        return in_array($this->cost_type, [
            self::TYPE_REPAIR,
            self::TYPE_PARTS,
            self::TYPE_LABOR,
        ]);
    }

    /**
     * وضع علامة كمزامن مع النظام المالي
     */
    public function markAsSynced(): void
    {
        $this->update(['synced_to_financial' => true]);
    }

    // ==================== Boot ====================

    protected static function booted()
    {
        static::creating(function ($cost) {
            if (empty($cost->created_by)) {
                $cost->created_by = auth()->id();
            }
            if (empty($cost->cost_date)) {
                $cost->cost_date = now();
            }
        });
    }

    /**
     * الحصول على أنواع التكاليف للـ Select
     */
    public static function getTypeOptions(): array
    {
        return self::TYPE_LABELS;
    }
}
