<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * InventorySummary
 * 
 * ملخص المخزون المحسوب مسبقاً لاستعلامات سريعة
 */
class InventorySummary extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'inventory_summary';

    protected $fillable = [
        'store_id',
        'product_id',
        'unit_id',
        'package_size',
        'remaining_qty',
    ];

    protected $casts = [
        'package_size' => 'float',
        'remaining_qty' => 'float',
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // Relationships
    // ═══════════════════════════════════════════════════════════════════════════

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Helper Methods
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Get or create a summary record
     */
    public static function getOrCreate(int $storeId, int $productId, int $unitId, float $packageSize = 1): self
    {
        return self::firstOrCreate(
            [
                'store_id' => $storeId,
                'product_id' => $productId,
                'unit_id' => $unitId,
            ],
            [
                'package_size' => $packageSize,
                'remaining_qty' => 0,
            ]
        );
    }

    /**
     * إضافة كمية واردة
     */
    public function addQty(float $quantity)
    {
        return $this->increment('remaining_qty', $quantity);
    }

    /**
     * طرح كمية صادرة
     */
    public function subtractQty(float $quantity)
    {
        return $this->decrement('remaining_qty', $quantity);
    }

    public function setQty(float $quantity)
    {
        return $this->update(['remaining_qty' => $quantity]);
    }

    // دالة لاسترجاع الرصيد مع البيانات المرتبطة بسرعة عالية
    public function scopeWithDetails($query)
    {
        // return $query;
        return $query->with([
            'product' => fn($q) => $q->select('id', 'name', 'code')->without('unitPrices'),

        ]);
    }

    public function scopeAvailable($query)
    {
        return $query->where('remaining_qty', '>', 0);
    }
}
