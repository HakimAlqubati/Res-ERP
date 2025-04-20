<?php

namespace App\Models;

use App\Services\MultiProductsInventoryService;
use App\Services\ProductCostingService;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;

class Order extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    public const ORDERED = 'ordered';
    public const PROCESSING = 'processing';
    public const READY_FOR_DELEVIRY = 'ready_for_delivery';
    public const DELEVIRED = 'delevired';
    public const PENDING_APPROVAL = 'pending_approval';

    public const METHOD_FIFO = 'fifo';
    public const METHOD_UNIT_PRICE = 'from_unit_prices';

    // Define constants for order types
    public const TYPE_NORMAL = 'normal';
    public const TYPE_MANUFACTURING = 'manufacturing';
    protected $fillable = [
        'id',
        'customer_id',
        'status',
        'branch_id',
        'recorded',
        'notes',
        'description',
        'full_quantity',
        'total',
        'active',
        'updated_by',
        'storeuser_id_update',
        'transfer_date',
        'is_purchased',
        'supplier_id',
        'order_date',
        'store_id',
        'cancelled',
        'cancel_reason',
        'type',
    ];
    protected $auditInclude = [
        'customer_id',
        'status',
        'branch_id',
        'recorded',
        'notes',
        'description',
        'full_quantity',
        'total',
        'active',
        'updated_by',
        'storeuser_id_update',
        'transfer_date',
        'is_purchased',
        'order_date',
        'store_id',
        'cancelled',
        'cancel_reason',
        'type',
    ];

    protected $appends = [
        'status_log_date_time',
        'status_log_creator_name',
        'store_names',
        'store_ids',
    ];

    // protected $casts = [
    //     'stores' => 'array',
    // ];


    public function orderDetails()
    {
        return $this->hasMany(OrderDetails::class);
    }
    public function orderDetails2()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // attribute to get branch name
    public function getBranchNameAttribute()
    {
        if ($this->branch) {
            return $this?->branch?->name;
        }

        return null;
    }

    public function scopeReadyForDelivery($query)
    {
        return $query->where('status', self::READY_FOR_DELEVIRY);
    }

    public function scopeInTransfer($query)
    {
        return $query->select('orders.*')
            ->join('orders_details', 'orders_details.order_id', '=', 'orders.id')
            ->where('orders_details.available_in_store', 1)->distinct();
    }

    public function storeEmpResponsiple()
    {
        return $this->belongsTo(User::class, 'storeuser_id_update');
    }

    public function customer_name()
    {
        return 'dddd';
    }

    // attribute to get items count
    public function getItemCountAttribute()
    {
        return $this->orderDetails?->count();
    }
    // attribute to get total amount
    public function getTotalAmountAttribute()
    {
        return $this->orderDetails?->sum(function ($detail) {
            return $detail->price * $detail->available_quantity;
        });
        // return $this->orderDetails?->sum('price');
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }


    // Status Labels
    public static function getStatusLabels(): array
    {
        return [
            self::ORDERED => 'Ordered',
            self::PROCESSING => 'Processing',
            self::READY_FOR_DELEVIRY => 'Ready for Delivery',
            self::DELEVIRED => 'Delivered',
            self::PENDING_APPROVAL => 'Pending Approval',
        ];
    }

    public static function getBadgeColor(string $status): string
    {
        return match ($status) {
            self::ORDERED => 'blue',
            self::PROCESSING => 'yellow',
            self::READY_FOR_DELEVIRY => 'orange',
            self::DELEVIRED => 'green',
            self::PENDING_APPROVAL => 'purple',
            default => 'gray',
        };
    }

    public static function getStatusIcon(string $status): string
    {
        return match ($status) {
            self::ORDERED => 'heroicon-o-shopping-cart',
            self::PROCESSING => 'heroicon-o-cog',
            self::READY_FOR_DELEVIRY => 'heroicon-o-truck',
            self::DELEVIRED => 'heroicon-o-check-circle',
            self::PENDING_APPROVAL => 'heroicon-o-clock',
            default => 'heroicon-o-exclamation-circle',
        };
    }

    public function cancelOrder(string $reason)
    {
        DB::beginTransaction();

        try {

            $this->cancelled = true;
            $this->cancel_reason = $reason;
            $this->save();

            // Delete related inventory transactions
            \App\Models\InventoryTransaction::where('transactionable_id', $this->id)
                ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_OUT)
                ->delete();

            DB::commit();

            return ['status' => 'success', 'message' => 'Order canceled successfully.'];
        } catch (Exception $e) {
            DB::rollBack();

            return ['status' => 'error', 'message' => 'Failed to cancel order: ' . $e->getMessage()];
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($order) {

            // Send notification to users with role ID = 5
            DB::afterCommit(function () use ($order) {

                $storeUsers = \App\Models\User::stores()->whereNotNull('fcm_token')->get();
                foreach ($storeUsers as $user) {
                    sendNotification(
                        $user->fcm_token,
                        '📦 طلب جديد تم إنشاؤه',
                        "طلب رقم #{$order->id} تم إنشاؤه بنجاح."
                    );
                }
            });
            OrderLog::create([
                'order_id'   => $order->id,
                'created_by' => auth()->id() ?? null,
                'log_type'   => OrderLog::TYPE_CREATED,
                'message'    => OrderLog::TYPE_CREATED,
                'new_status' => $order->status,
            ]);
        });

        static::updated(function ($order) {

            if (in_array($order->status, [self::PROCESSING, self::READY_FOR_DELEVIRY]) && $order->isDirty('status')) {
                $customer = $order->customer;
                if ($customer && $customer->fcm_token) {
                    sendNotification(
                        $customer->fcm_token,
                        '📦 تحديث حالة الطلب',
                        "تم تحديث حالة طلبك رقم #{$order->id} إلى: " . self::getStatusLabels()[$order->status]
                    );
                }
            }


            if (
                $order->status === self::READY_FOR_DELEVIRY &&
                $order->getOriginal('status') !== self::READY_FOR_DELEVIRY
            ) {
                // (new \App\Services\Orders\OrderInventoryAllocator($order))
                //     ->allocateFromManagedStores(auth()->user()?->managed_stores_ids ?? []);
                foreach ($order->orderDetails as $detail) {
                    $fifoService = new \App\Services\MultiProductsInventoryService(null, null, $detail->unit_id, null);

                    $allocations = $fifoService->allocateFIFO(
                        $detail->product_id,
                        $detail->unit_id,
                        $detail->available_quantity // الكمية المطلوبة للصرف
                        ,
                        $order
                    );


                    foreach ($allocations as $alloc) {
                        \App\Models\InventoryTransaction::create([
                            'product_id'           => $detail->product_id,
                            'movement_type'        => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                            'quantity'             => $alloc['deducted_qty'],
                            'unit_id'              => $alloc['target_unit_id'],
                            'package_size'         => $alloc['target_unit_package_size'],
                            'price'                => $alloc['price_based_on_unit'],
                            'movement_date'        => $order->order_date ?? now(),
                            'transaction_date'     => $order->order_date ?? now(),
                            'store_id'             => $alloc['store_id'],
                            'notes' => "Stock deducted for Order #{$order->id} from " .
                                match ($alloc['transactionable_type'] ?? null) {
                                    'PurchaseInvoice'     => 'Purchase Invoice',
                                    'StockSupplyOrder'    => 'Stock Supply',
                                    default               => 'Unknown Source',
                                } .
                                " #" . ($alloc['transactionable_id'] ?? 'N/A') .
                                " with price " . number_format($alloc['price_based_on_unit'], 2),
                            ($alloc['transaction_id'] ?? 'N/A') .
                                " with price " . number_format($alloc['price_based_on_unit'], 2),
                            'transactionable_id'   => $order->id,
                            'transactionable_type' => \App\Models\Order::class,
                            'source_transaction_id' => $alloc['transaction_id'],

                        ]);
                    }
                }

                // ✅ New logic: Update costing for composite (manufacturing) product when a component product is affected

                foreach ($order->orderDetails as $detail) {
                    $parentProducts = ProductItem::whereIn('product_id', $order->orderDetails->pluck('product_id')->toArray())
                        ->pluck('parent_product_id')
                        ->unique();

                    foreach ($parentProducts as $parentProductId) {
                        try {
                            $count = ProductCostingService::updateComponentPricesForProduct($parentProductId);
                            Log::info("🔄 تم تحديث أسعار {$count} مكونات لـ منتج مركب ID {$parentProductId}");
                        } catch (\Throwable $e) {
                            Log::error("❌ خطأ أثناء تحديث سعر المنتج المركب {$parentProductId}: {$e->getMessage()}");
                        }
                    }
                }
            }



            if ($order->isDirty('status')) {
                OrderLog::create([
                    'order_id'   => $order->id,
                    'created_by' => auth()->id() ?? null,
                    'log_type'   => 'change_status',
                    'message'    => 'Order status changed from ' .
                        $order->getOriginal('status') .
                        ' to ' . $order->status,
                    'new_status' => $order->status,
                ]);
            }
        });
    }

    /**
     * Get possible next statuses based on the current status
     *
     * @return array
     */
    public function getNextStatuses()
    {
        switch ($this->status) {
            case self::ORDERED:
                return [
                    // self::STATUS_PENDING => 'Pending',
                    self::PROCESSING => 'PROCESSING',
                ];
            case self::PROCESSING:
                return [
                    // self::STATUS_PENDING => 'Pending',
                    self::READY_FOR_DELEVIRY => 'Ready For Delivery',
                ];
                //     ];
            case self::READY_FOR_DELEVIRY:
                return [
                    self::DELEVIRED => 'Delevired',
                ];
            default:
                return []; // No transitions available for final statuses
        }
    }
    public function logs()
    {
        return $this->hasMany(OrderLog::class);
    }

    public function getStatusLogDateTimeAttribute()
    {
        $log = $this->logs()
            ->where('new_status', $this->status)
            ->latest('created_at')
            ->first();
        return $log ? $log->created_at->format('Y-m-d H:i:s') : null;
    }

    public function getStatusLogCreatorNameAttribute()
    {
        $log = $this->logs()
            ->where('new_status', $this->status)
            ->latest('created_at')
            ->first();
        return $log && $log->creator ? $log->creator->name : null;
    }

    public function getNextStatusLabel()
    {
        $nextStatuses = $this->getNextStatuses();
        return $nextStatuses ? reset($nextStatuses) : null;
    }

    /**
     * Scope a query to only include normal orders.
     */
    public function scopeNormal($query)
    {
        return $query->where('type', self::TYPE_NORMAL);
    }

    /**
     * Scope a query to only include manufacturing orders.
     */
    public function scopeManufacturing($query)
    {
        return $query->where('type', self::TYPE_MANUFACTURING);
    }

    public function scopeHasManufacturingProducts($query)
    {
        return $query->whereHas('orderDetails', function ($q) {
            $q->whereHas('product.category', function ($q2) {
                $q2->where('is_manafacturing', true);
            });
        });
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'order_store');
    }
    public function getStoreNamesAttribute()
    {
        return $this->stores->pluck('name')->implode(', ');
    }
    public function getStoreIdsAttribute()
    {
        return $this->stores->pluck('id')->toArray();
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
