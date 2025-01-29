<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoiceDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->quantity * $this->price;
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($detail) {
            
            // Find the original quantity before update
            $originalQuantity = $detail->getOriginal('quantity');

            $inventory = Inventory::firstOrNew([
                'product_id' => $detail->product_id,
                'unit_id' => $detail->unit_id,
            ]);

            // Adjust inventory: Remove old quantity and add new quantity
            $inventory->quantity = ($inventory->quantity ?? 0) - $originalQuantity + $detail->quantity;
            $inventory->save();
        });
    }
}
