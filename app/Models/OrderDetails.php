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
        'package_size',
    ];

    protected $appends = ['total_price'];
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
    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($orderDetail) {
    //         $orderDetail->available_quantity = $orderDetail->quantity;
    //     });
    //     static::created(function ($orderDetail) {
    //         $notes = 'Order with id ' . $orderDetail->order_id;
    //         if (isset($orderDetail->order->store_id)) {
    //             $notes .= ' in (' . $orderDetail->order->store->name . ')';
    //         }
    //         // Subtract from inventory transactions
    //         \App\Models\InventoryTransaction::create([
    //             'product_id' => $orderDetail->product_id,
    //             'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_ORDERS,
    //             'quantity' =>  $orderDetail->quantity,
    //             'unit_id' => $orderDetail->unit_id,
    //             'movement_date' => now(),
    //             'package_size' => $orderDetail->package_size,
    //             'reference_id' => $orderDetail->order_id,
    //             'store_id' => $orderDetail->order?->store_id,
    //             'notes' => $notes,
    //         ]);
    //     });
    // }

    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }
}
