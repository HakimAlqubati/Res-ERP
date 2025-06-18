<?php

namespace App\Models;

use App\Services\ProductCostingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;

class PurchaseInvoiceDetail extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'package_size',
        'waste_stock_percentage',
        'unit_total_price',
    ];
    protected $auditInclude = [
        'purchase_invoice_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'package_size',
        'waste_stock_percentage',
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

            $invoice = $purchaseInvoiceDetail->purchaseInvoice;

            $grn = $invoice->grn;

            if ($grn && $grn->has_inventory_transaction) {
                return;
            }

            $notes = 'Purchase invoice with id ' . $purchaseInvoiceDetail->purchase_invoice_id;
            if (isset($purchaseInvoiceDetail->purchaseInvoice->store_id)) {
                $notes .= ' in (' . $purchaseInvoiceDetail->purchaseInvoice->store->name . ')';
            }

            \App\Services\UnitPriceFifoUpdater::updateIfInventoryIsZero(
                $purchaseInvoiceDetail->product_id,
                $purchaseInvoiceDetail->unit_id,
                $purchaseInvoiceDetail->price,
                $purchaseInvoiceDetail->package_size,
                $invoice->store_id,
                $invoice->date ?? now(),
                'Updated from Purchase Invoice #' . $purchaseInvoiceDetail->purchase_invoice_id
            );
                                        
            // Add a record to the inventory transactions table
            \App\Models\InventoryTransaction::create([
                'product_id' => $purchaseInvoiceDetail->product_id,
                'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_IN,
                'quantity' => $purchaseInvoiceDetail->quantity,
                'package_size' => $purchaseInvoiceDetail->package_size,
                'price' => $purchaseInvoiceDetail->price,
                'movement_date' => $purchaseInvoiceDetail->purchaseInvoice->date ?? now(),
                'unit_id' => $purchaseInvoiceDetail->unit_id,
                'store_id' => $purchaseInvoiceDetail->purchaseInvoice?->store_id,
                'notes' => $notes,
                'transaction_date' => $purchaseInvoiceDetail->purchaseInvoice->date ?? now(),
                'transactionable_id' => $purchaseInvoiceDetail->purchase_invoice_id,
                'transactionable_type' => PurchaseInvoice::class,
                'waste_stock_percentage' => $purchaseInvoiceDetail->waste_stock_percentage,
            ]);


            // ✅ تحديث السعر بعد إضافة الحركة
            // \App\Services\UnitPriceFifoUpdater::updatePriceUsingFifo(
            // $purchaseInvoiceDetail->product_id,
            // $purchaseInvoiceDetail->purchaseInvoice
            // );
        });
    }
    public function inventoryTransactions()
    {
        return \App\Models\InventoryTransaction::query()
            ->where('product_id', $this->product_id)
            ->where('unit_id', $this->unit_id)
            ->where('transactionable_id', $this->purchase_invoice_id)
            ->where('transactionable_type', \App\Models\PurchaseInvoice::class);
    }
}
