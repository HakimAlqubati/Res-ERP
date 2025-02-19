<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const ORDERED = 'ordered';
    public const PROCESSING = 'processing';
    public const READY_FOR_DELEVIRY = 'ready_for_delivery';
    public const DELEVIRED = 'delevired';
    public const PENDING_APPROVAL = 'pending_approval';
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

        return $this->orderDetails?->sum('price');
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
}
