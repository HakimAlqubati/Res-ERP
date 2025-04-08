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
        return $query;
        $order = $query->first()?->order;

        // فقط لو الطلب مو من إنشاء نفس المستخدم
        if (
            $order && $order->created_by !== auth()->id()
            || (isset($order->customer_id) && $order->customer_id !== auth()->id())
        ) {
            $user = auth()->user();
            $branch = $user->branch;

            // فقط لو كان مطبخ مركزي
            if ($branch && $branch->is_kitchen) {
                // التخصصات المخصصة لهذا الفرع
                $customizedCategoriesIds = $branch->categories()->pluck('categories.id')->toArray();

                // التخصصات المخصصة لفروع المطابخ المركزية الأخرى
                $otherBranchesCategories = \App\Models\Branch::centralKitchens()
                    ->where('id', '!=', $branch->id) // نستثني فرع المستخدم
                    ->with('categories:id')
                    ->get()
                    ->pluck('categories')
                    ->flatten()
                    ->pluck('id')
                    ->unique()
                    ->toArray();
                return $query->whereHas('product.category', function ($q) use ($customizedCategoriesIds, $otherBranchesCategories) {


                    $q->where('is_manafacturing', true)
                        ->when(
                            count($customizedCategoriesIds),
                            function ($query) use ($customizedCategoriesIds) {

                                // ✅ إذا عنده تخصصات → يرجع فقط تخصصاته
                                $query->whereIn('id', $customizedCategoriesIds);
                            },
                            function ($query) use ($otherBranchesCategories) {
                                // ❌ إذا ما عنده → يستثني تخصصات الفروع الأخرى
                                $query->whereNotIn('id', $otherBranchesCategories);
                            }
                        )
                    ;
                });
            }

            // باقي المستخدمين يشوفون فقط المنتجات التصنيعية
            return $query->whereHas('product.category', function ($q) {
                $q->where('is_manafacturing', true);
            });
        }

        return $query;
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
