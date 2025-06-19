<?php

namespace App\Models;

use App\Services\UnitPriceSyncService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class UnitPrice extends Model implements Auditable

{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $table = 'unit_prices';
    public $primaryKey = 'id';
    protected $fillable = [
        'unit_id',
        'product_id',
        'price',
        'package_size',
        'order',
        'minimum_quantity',
        'show_in_invoices',
        'use_in_orders',
        'date',
        'notes',
    ];
    protected $auditInclude = [
        'unit_id',
        'product_id',
        'price',
        'package_size',
        'order',
        'minimum_quantity',
        'show_in_invoices'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function toArray()
    {
        return [
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit->name,
            'price' => $this->price,
            'package_size' => $this->package_size,
            'order' => $this->order,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $unitPrice) {
            // فقط إذا تم تغيير السعر
            if ($unitPrice->isDirty('price') && $unitPrice->price != 0) {
                \App\Models\ProductPriceHistory::create([
                    'product_id'     => $unitPrice->product_id,
                    'unit_id'        => $unitPrice->unit_id,
                    'old_price'      => $unitPrice->getOriginal('price') ?? 0,
                    'new_price'      => $unitPrice->price,
                    'date'           => $unitPrice->date ?? now(), // استخدم التاريخ المحدد أو تاريخ اليوم
                    'note'           => $unitPrice->notes ?? 'تحديث تلقائي من UnitPrice',
                    'source_type'    => self::class,
                    'source_id'      => $unitPrice->id,
                ]);
            }
        });
        // static::saved(function (self $unitPrice) {
        //     // Automatically update package sizes in related tables
        //     // UnitPriceSyncService::syncPackageSizeForProduct($unitPrice->product_id);
        // });
    }


    public function scopeShowInInvoices($query)
    {
        return $query->where('show_in_invoices', true);
    }
}
