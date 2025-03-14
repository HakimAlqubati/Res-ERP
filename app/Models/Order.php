<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
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
        'status_log_creator_name'
    ];

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
            if ($order->status == self::ORDERED) {
                // Get store users with role ID 5
                $storeUsers = User::whereHas("roles", function ($q) {
                    $q->where("id", 5);
                })->pluck('fcm_token')->filter()->toArray(); // Get device tokens

                // Send notification to store users
                foreach ($storeUsers as $deviceToken) {
                    sendNotification(
                        $deviceToken,
                        'New Order Received',
                        'A new order #' . $order->id . ' has been placed and needs processing.'
                    );
                }
            }
            OrderLog::create([
                'order_id'   => $order->id,
                'created_by' => auth()->id() ?? null,
                'log_type'   => OrderLog::TYPE_CREATED,
                'message'    => OrderLog::TYPE_CREATED,
                'new_status' => $order->status,
            ]);
        });

        static::updated(function ($order) {

            // Check if status was updated
            if ($order->isDirty('status')) {
                // Handle specific status changes
                switch ($order->status) {
                    case self::PROCESSING:
                    case self::READY_FOR_DELEVIRY:
                        // Notify the customer (order's customer_id)
                        if ($order->customer && $order->customer->fcm_token) {
                            sendNotification(
                                $order->customer->fcm_token,
                                'Order Update',
                                'Your order #' . $order->id . ' is now ' . ucfirst(str_replace('_', ' ', $order->status)) . '.'
                            );
                        }
                        break;

                    case self::DELEVIRED:
                        // Notify store users with role ID 5
                        $storeUsers = User::whereHas("roles", function ($q) {
                            $q->where("id", 5);
                        })->pluck('fcm_token')->filter()->toArray();

                        foreach ($storeUsers as $deviceToken) {
                            sendNotification(
                                $deviceToken,
                                'Order Delivered',
                                'Order #' . $order->id . ' has been successfully delivered.'
                            );
                        }
                        break;
                }
            }


            if (
                $order->status === self::READY_FOR_DELEVIRY &&
                $order->getOriginal('status') !== self::READY_FOR_DELEVIRY
            ) {

                // if (!$order->store_id && isStoreManager() && !is_null(getDefaultStoreForCurrentStoreKeeper())) {
                //     $order->update(['store_id' => getDefaultStoreForCurrentStoreKeeper()]);
                // }
                // if ($order->type == self::TYPE_NORMAL) {
                foreach ($order->orderDetails as $orderDetail) {
                    $storeId = getDefaultStoreForCurrentStoreKeeper();
                    if ($orderDetail->product->is_manufacturing) {
                        $storeId = Store::centralKitchen()->id;
                    }
                    \App\Models\InventoryTransaction::create([
                        'product_id'           => $orderDetail->product_id,
                        'movement_type'        => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                        'quantity'             => $orderDetail->available_quantity,
                        'unit_id'              => $orderDetail->unit_id,
                        'purchase_invoice_id'  => $orderDetail->purchase_invoice_id,
                        'movement_date'        => $order->order_date ?? now(),
                        'package_size'         => $orderDetail->package_size,
                        'store_id'             => $storeId,
                        'transaction_date'     => $order->order_date ?? now(),
                        'notes'                => 'Inventory created for order ' . $order->id,
                        'transactionable_id'   => $order->id,
                        'transactionable_type' => Order::class,
                    ]);
                }
                // } elseif ($order->type == self::TYPE_MANUFACTURING) {
                //     foreach ($order->orderDetails as $orderDetail) {
                //         $detailAvailableQty =  $orderDetail->available_quantity;
                //         $detailPackageSize = $orderDetail->package_size;

                //         $manufacturingService = new \App\Services\Products\Manufacturing\ProductManufacturingService();
                //         $manafcturingProduct = $manufacturingService->getProductItems($orderDetail->product_id);
                //         $productItems = $manafcturingProduct['product_items'];
                //         $unitPrices = $manafcturingProduct['unit_prices'];

                //         foreach ($productItems as  $productItem) {
                //             \App\Models\InventoryTransaction::create([
                //                 'product_id'           => $productItem->product_id,
                //                 'movement_type'        => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                //                 'quantity'             => $detailAvailableQty * $productItem->quantity  * $detailPackageSize,
                //                 'unit_id'              => $productItem->unit_id,
                //                 'purchase_invoice_id'  => null,
                //                 'movement_date'        => $order->order_date ?? now(),
                //                 'package_size'         => $productItem->package_size,
                //                 'store_id'             => $order->store_id,
                //                 'transaction_date'     => $order->order_date ?? now(),
                //                 'notes'                => 'Inventory created for manafacturing order ' . $order->id,
                //                 'transactionable_id'   => $order->id,
                //                 'transactionable_type' => Order::class,
                //             ]);
                //         }
                //     }
                //   }
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
}
