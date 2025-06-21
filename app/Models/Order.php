<?php

namespace App\Models;

use App\Models\Scopes\OrderScopes;
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
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, OrderScopes;

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



    public function storeEmpResponsiple()
    {
        return $this->belongsTo(User::class, 'storeuser_id_update');
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
                foreach ($order->orderDetails as $detail) {
                    $fifoService = new \App\Services\FifoMethodService($order);

                    $allocations = $fifoService->getAllocateFifo(
                        $detail->product_id,
                        $detail->unit_id,
                        $detail->available_quantity
                    );

                    self::moveFromInventory($allocations, $detail);

                    if ($order->branch && $order->branch->store && $order->branch->store->active) {
                        self::receiveIntoBranchStore($allocations, $detail);
                    }
                    // if (!$branchStoreId || !$order->branch->store->active) {
                    //     self::moveFromInventory($allocations, $detail);
                    // } else if ($branchStoreId && $order->branch->store->active) {
                    //     self::createStockTransferOrder($allocations, $detail);
                    // }
                }

                // ✅ New logic: Update costing for composite (manufacturing) product when a component product is affected

                // foreach ($order->orderDetails as $detail) {
                // $parentProducts = ProductItem::whereIn('product_id', $order->orderDetails->pluck('product_id')->toArray())
                //     ->pluck('parent_product_id')
                //     ->unique();

                // foreach ($parentProducts as $parentProductId) {
                //     try {
                //         // $count = ProductCostingService::updateComponentPricesForProduct($parentProductId);
                //         // Log::info("🔄 تم تحديث أسعار {$count} مكونات لـ منتج مركب ID {$parentProductId}");
                //     // } catch (\Throwable $e) {
                //         // Log::error("❌ خطأ أثناء تحديث سعر المنتج المركب {$parentProductId}: {$e->getMessage()}");
                //     }
                // }
                // }
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

        static::saved(function (Order $order) {
            if (in_array($order->status, [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])) {
                app(\App\Services\CopyOrderOutToBranchStoreService::class)->handleForOrder($order);
            }
        });
    }


    public static function moveFromInventory($allocations, $detail)
    {
        $order = $detail->order;
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
                'notes' => $alloc['notes'],

                'transactionable_id'   => $detail->order_id,
                'transactionable_type' => \App\Models\Order::class,
                'source_transaction_id' => $alloc['transaction_id'],

            ]);
        }
        return;
    }


    public static function receiveIntoBranchStore($allocations, $detail)
    {
        $order = $detail->order;
        $targetStoreId = $order->branch->store->id;

        foreach ($allocations as $alloc) {
            \App\Models\InventoryTransaction::create([
                'product_id'           => $detail->product_id,
                'movement_type'        => \App\Models\InventoryTransaction::MOVEMENT_IN,
                'quantity'             => $alloc['deducted_qty'],
                'unit_id'              => $alloc['target_unit_id'],
                'package_size'         => $alloc['target_unit_package_size'],
                'price'                => $alloc['price_based_on_unit'],
                'movement_date'        => $order->order_date ?? now(),
                'transaction_date'     => $order->order_date ?? now(),
                'store_id'             => $targetStoreId,
                'notes'                => $alloc['notes'],
                'transactionable_id'   => $detail->order_id,
                'transactionable_type' => \App\Models\Order::class,
                'source_transaction_id' => $alloc['transaction_id'],
            ]);
        }
    }


    public static function createStockTransferOrder($allocations, $detail)
    {
        $order = $detail->order; // لضمان توفره
        $branchStoreId = $order->branch?->store_id;
        if ($branchStoreId) {
            // ✅ إذا يوجد مخزن للفرع، نقوم بإنشاء أمر تحويل مخزني معتمد
            $transferOrder = \App\Models\StockTransferOrder::create([
                'from_store_id' => $allocations[0]['store_id'], // نفترض الكل من نفس المخزن
                'to_store_id'   => $branchStoreId,
                'date'          => $order->order_date ?? now(),
                'status'        => \App\Models\StockTransferOrder::STATUS_APPROVED,
                'notes'         => "Auto transfer for Order #{$order->id}",
            ]);

            foreach ($allocations as $alloc) {
                // تفاصيل التحويل
                \App\Models\StockTransferOrderDetail::create([
                    'stock_transfer_order_id' => $transferOrder->id,
                    'product_id'              => $detail->product_id,
                    'unit_id'                 => $alloc['target_unit_id'],
                    'quantity'                => $alloc['deducted_qty'],
                    'price'                   => $alloc['price_based_on_unit'],
                    'package_size'            => $alloc['target_unit_package_size'],
                    'note'                    => $alloc['notes'],
                ]);
            }


            $transferOrder->createInventoryTransactionsFromTransfer();
        }
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

    public function getDeliveryInfo(): ?array
    {
        $log = $this->logs()
            ->where('new_status', self::DELEVIRED)
            ->latest('created_at')
            ->with('creator') // تأكد أن العلاقة موجودة في OrderLog
            ->first();

        if (!$log) {
            return null; // لم يتم تسليم الطلب بعد
        }

        return [
            'id'     => $this->id,
            'do_number'     => now()->format('Ymd') . str_pad($this->id, 4, '0', STR_PAD_LEFT),
            'do_date'       => $log->created_at->format('Y-m-d'),
            'delivered_by'  => $log->creator?->name ?? 'N/A',
            'customer_name' => $this->customer?->name ?? $this->branch?->name ?? 'N/A',
            'branch_address' => $this->branch?->address ?? 'N/A',

            'items' => $this->orderDetails->map(fn($item, $i) => [
                'index'     => $i + 1,
                'name'      => $item->product?->name,
                'unit'      => $item->unit?->name ?? '-',  // ✅ أضف هذه السطر
                'quantity'  => $item->available_quantity,
            ]),
            'total_qty'     => $this->orderDetails->sum('available_quantity'),
        ];
    }
}
