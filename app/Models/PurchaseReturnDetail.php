<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PurchaseReturnDetail extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'purchase_return_id',
        'purchase_invoice_detail_id',
        'product_id',
        'unit_id',
        'package_size',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $auditInclude = [
        'purchase_return_id',
        'purchase_invoice_detail_id',
        'product_id',
        'unit_id',
        'package_size',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'package_size' => 'decimal:4',
        'unit_price'   => 'decimal:4',
        'total_price'  => 'decimal:4',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function purchaseInvoiceDetail()
    {
        return $this->belongsTo(PurchaseInvoiceDetail::class, 'purchase_invoice_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $detail) {
            $detail->total_price = round(((float) $detail->quantity) * ((float) $detail->unit_price), 4);
        });
    }
}
