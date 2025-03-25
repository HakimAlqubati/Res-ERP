<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class OrderDetails extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
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
    protected $auditInclude = [
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
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orderDetail) {
            $orderDetail->orderd_product_id = $orderDetail->product_id;
            $orderDetail->ordered_unit_id = $orderDetail->unit_id;
            $orderDetail->available_quantity = $orderDetail->quantity;
        });
        static::updated(function ($orderDetail) {
            $order = $orderDetail->order;
            $dirty = $orderDetail->getDirty();
            $messageParts = [];

            // Check if unit_id was updated
            if (isset($dirty['unit_id'])) {
                // Get the corresponding UnitPrice record
                $unitPrice = UnitPrice::where('product_id', $orderDetail->product_id)
                    ->where('unit_id', $orderDetail->unit_id)
                    ->first();

                if ($unitPrice) {
                    $orderDetail->package_size = $unitPrice->package_size;
                    $orderDetail->save();
                }
            }

            foreach ($dirty as $field => $newValue) {
                $oldValue = $orderDetail->getOriginal($field);
                $messageParts[] = "$field: $oldValue -> $newValue";
            }
            $message = "Updated fields: " . implode(', ', $messageParts);
            if ($order) {
                OrderLog::create([
                    'order_id'   => $order->id,
                    'created_by' => auth()->id() ?? null,
                    'log_type'   => OrderLog::TYPE_UPDATED,
                    'message'    => $message,
                    'new_status' => 'NOT',
                ]);
            }
        });
        // static::created(function ($orderDetail) {
        //     $notes = 'Order with id ' . $orderDetail->order_id;
        //     if (isset($orderDetail->order->store_id)) {
        //         $notes .= ' in (' . $orderDetail->order->store->name . ')';
        //     }
        //     // Subtract from inventory transactions
        //     \App\Models\InventoryTransaction::create([
        //         'product_id' => $orderDetail->product_id,
        //         'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_OUT,
        //         'quantity' =>  $orderDetail->quantity,
        //         'unit_id' => $orderDetail->unit_id,
        //         'purchase_invoice_id' => $orderDetail?->purchase_invoice_id,
        //         'movement_date' => $orderDetail->order->date ?? now(),
        //         'package_size' => $orderDetail->package_size,
        //         'store_id' => $orderDetail->order?->store_id,
        //         'transaction_date' => $orderDetail->order->date ?? now(),
        //         'notes' => $notes,
        //         'transactionable_id' => $orderDetail->order_id,
        //         'transactionable_type' => Order::class,
        //     ]);
        // });
    }

    public function scopeManufacturingOnlyForStore($query)
    {
        
        if ($query->first()->order->branch_id != auth()->user()->branch->id) {
            return $query->whereHas('product.category', function ($q) {
                $q->where('is_manafacturing', true);
            });
        }
    }
    public function getTotalPriceAttribute()
    {
        return $this->available_quantity * $this->price;
    }

    public function getPriceWithCurrencyAttribute()
    {
        return formatMoney($this->price);
    }
    public function getTotalPriceWithCurrencyAttribute()
    {
        $res = $this->available_quantity * $this->price;
        return formatMoney($res);
    }
}
