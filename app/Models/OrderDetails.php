<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    use HasFactory;
    protected $table = 'orders_details';
    protected $fillable = [
        'order_id',
        'product_id',
        'unit_id',
        'quantity',
        'available_quantity',
        'price',
        'available_in_store',
        'created_by',
        'updated_at',
        'created_at',
        'updated_by',
        'purchase_invoice_id',
        'negative_inventory_quantity',
        'orderd_product_id',
        'ordered_unit_id',
    ];


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function toArray()
    {
        return [
            'order_detail_id' => $this->id,
            'product' => [
                'id' => $this->product_id,
                'name' => $this->product->name,
            ],
            'unit' => [
                'unit' => $this->unit_id,
                'unit_name' => $this->unit->name
            ],
            'quantity' => $this->quantity,
            'price' => $this->price,
            'available_quantity' => $this->available_quantity,
            'available_in_store' => $this->available_in_store,
        ];
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function getPurchaseInvoiceNoAttribute()
    {
        $invoiceNo = 'None';
        $purchaseInvoice = $this->purchaseInvoice;
        if ($purchaseInvoice) {
            $invoiceNo = '(' . $purchaseInvoice->id . ') ' . $purchaseInvoice->invoice_no;
        }
        return $invoiceNo;
    }
    public function ordered_product()
    {
        return $this->belongsTo(Product::class, 'orderd_product_id');
    }

    public function orderd_unit()
    {
        return $this->belongsTo(Unit::class);
    }


    protected static function booted()
    {
        // Handle inventory update on create
        static::created(function ($detail) {
            $inventory = Inventory::where('product_id', $detail->product_id)
                ->where('unit_id', $detail->unit_id)
                ->first();

            if ($inventory) {
                // Deduct the ordered quantity from inventory
                $inventory->quantity -= $detail->quantity;
                $inventory->save();
            }
        });

        // Handle inventory adjustment on update
        static::updated(function ($detail) {
            $inventory = Inventory::where('product_id', $detail->product_id)
                ->where('unit_id', $detail->unit_id)
                ->first();

            if ($inventory) {
                // Reverse the old quantity
                $originalQuantity = $detail->getOriginal('quantity');
                $inventory->quantity += $originalQuantity;

                // Deduct the new quantity
                $inventory->quantity -= $detail->quantity;
                $inventory->save();
            }
        });

        // Handle inventory adjustment on delete
        static::deleted(function ($detail) {
            $inventory = Inventory::where('product_id', $detail->product_id)
                ->where('unit_id', $detail->unit_id)
                ->first();

            if ($inventory) {
                // Add back the quantity when an order is deleted
                $inventory->quantity += $detail->quantity;
                $inventory->save();
            }
        });
    }
}
