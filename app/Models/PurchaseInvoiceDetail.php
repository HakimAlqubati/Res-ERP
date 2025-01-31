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
        'package_size',
    ];
    protected $appends = ['total_price'];


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



    /**
     * Calculate the total price (quantity * price).
     *
     * @return float
     */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($purchaseInvoiceDetail) {
            // Add a record to the inventory transactions table
            \App\Models\InventoryTransaction::create([
                'product_id' => $purchaseInvoiceDetail->product_id,
                'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_PURCHASE_INVOICE,
                'quantity' => $purchaseInvoiceDetail->quantity,
                'package_size' => $purchaseInvoiceDetail->package_size,
                'unit_id' => $purchaseInvoiceDetail->unit_id,
                'reference_id' => $purchaseInvoiceDetail->purchase_invoice_id,
                'notes' => 'Purchase Invoice Detail added',
            ]);
        });
    }
}
