<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PosSaleItem extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $table = 'pos_sale_items';

    protected $fillable = [
        'pos_sale_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'total_price',
        'package_size',
        'notes',
    ];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'price'        => 'decimal:2',
        'total_price'  => 'decimal:2',
        'package_size' => 'decimal:4',
    ];

    protected $auditInclude = [
        'pos_sale_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'total_price',
        'package_size',
        'notes',
    ];

    protected $appends = [
        'line_total_calculated',
    ];

    /*
    |--------------------------------------------------------------------------
    | علاقات
    |--------------------------------------------------------------------------
    */

    public function sale()
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * مجموع السطر محسوب (quantity * price) – حتى لو اختلف عن العمود المخزن.
     */
    public function getLineTotalCalculatedAttribute()
    {
        return (float) $this->quantity * (float) $this->price;
    }

    /**
     * هل بيانات الكمية/السعر/total_price متطابقة منطقيًا؟
     */
    public function getIsTotalSyncedAttribute(): bool
    {
        // نستخدم round لتفادي أخطاء الفاصلة العشرية
        return round($this->total_price, 2) === round($this->line_total_calculated, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    | ممكن نخلي الموديل يحدّث total_price تلقائيًا عند الحفظ.
    */

    protected static function boot()
    {
        parent::boot();

        static::saving(function (PosSaleItem $item) {
            // لو total_price غير محدد أو 0، نحسبه تلقائيًا
            if (is_null($item->total_price) || $item->total_price == 0) {
                $item->total_price = $item->quantity * $item->price;
            }

            // ضمان package_size لا تكون null
            if (is_null($item->package_size) || $item->package_size == 0) {
                $item->package_size = $item->package_size ?? 1;
            }
        });
    }
}
