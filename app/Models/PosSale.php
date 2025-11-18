<?php

namespace App\Models;

use App\Services\FifoMethodService;
use App\Traits\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;

class PosSale extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, BranchScope;

    // حالات السند
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'pos_sales';

    protected $fillable = [
        'branch_id',
        'store_id',
        'sale_date',
        'status',
        'total_quantity',
        'total_amount',
        'cancelled',
        'cancel_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sale_date'      => 'datetime',
        'total_quantity' => 'decimal:4',
        'total_amount'   => 'decimal:2',
        'cancelled'      => 'boolean',
    ];

    protected $auditInclude = [
        'branch_id',
        'store_id',
        'sale_date',
        'status',
        'total_quantity',
        'total_amount',
        'cancelled',
        'cancel_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'status_label',
        'status_badge_color',
        'formatted_sale_date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Booted Events
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::created(function (PosSale $sale) {
            // لو تم إنشاء السند وهو مكتمل، ننشئ حركات المخزون مباشرة
            if ($sale->status === self::STATUS_COMPLETED) {
                $sale->createInventoryTransactionsFromItems();
            }
        });

        // اختيارية: لو تحب تنشئ الحركات عند تغيير الحالة إلى مكتملة بعد الإنشاء

        static::updated(function (PosSale $sale) {
            if (
                $sale->wasChanged('status')
                && $sale->status === self::STATUS_COMPLETED
            ) {
                $sale->createInventoryTransactionsFromItems();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | علاقات
    |--------------------------------------------------------------------------
    */

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(PosSaleItem::class, 'pos_sale_id');
    }

    /**
     * حركات المخزون الناتجة عن هذا السند (polymorphic)
     */
    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'transactionable');
    }

    /*
    |--------------------------------------------------------------------------
    | دوال ثابتة / Status Helpers
    |--------------------------------------------------------------------------
    */

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT     => 'gray',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'red',
            default                => 'gray',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Mutators
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return self::getBadgeColor($this->status);
    }

    public function getFormattedSaleDateAttribute(): ?string
    {
        return $this->sale_date?->format('Y-m-d H:i');
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === self::STATUS_CANCELLED || (bool) $this->cancelled;
    }

    /**
     * إعادة حساب الإجماليات من البنود وتخزينها في السند.
     */
    public function recalculateTotals(): void
    {
        $totalQty    = $this->items->sum('quantity');
        $totalAmount = $this->items->sum('total_price');

        $this->total_quantity = $totalQty;
        $this->total_amount   = $totalAmount;
        $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Helpers
    |--------------------------------------------------------------------------
    */



    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

        /*
    |--------------------------------------------------------------------------
    | Inventory Helpers (FIFO)
    |--------------------------------------------------------------------------
    */

    /**
     * إنشاء حركات المخزون بناءً على تفاصيل السند (PosSaleItem) باستخدام FIFO
     * مشابه لمنطق Order::moveFromInventory + FifoMethodService
     */
    public function createInventoryTransactionsFromItems(): void
    {
        // منع التكرار: لو سبق وتم إنشاء حركات مرتبطة بهذا السند لا نعيدها
        if ($this->inventoryTransactions()->exists()) {
            return;
        }

        $this->loadMissing('items', 'store', 'branch');

        if ($this->items->isEmpty()) {
            return;
        }

        DB::beginTransaction();

        try {
            // نمرر السند للخدمة، مثل ما تعمل مع Order
            $fifoService = new FifoMethodService($this);

            foreach ($this->items as $item) {

                $productItems = $item?->product?->productItems;
                if (!$productItems) {
                    return;
                }
                foreach ($productItems as  $productItem) {

                    // نطلب من الخدمة تخصيص كميات FIFO
                    $allocations = $fifoService->getAllocateFifo(
                        $productItem->product_id,
                        $productItem->unit_id,
                        $productItem->quantity
                    );

                    // ننشئ حركات المخزون بناء على الـ allocations
                    self::moveFromInventoryForPos($allocations, $productItem, $this);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            // هنا ممكن تضيف Log أو exception حسب أسلوبك:
            // Log::error('POS FIFO error: ' . $e->getMessage(), ['pos_sale_id' => $this->id]);
            throw $e;
        }
    }

    /**
     * منطق إنشاء حركات المخزون للـ POS بناءً على تخصيصات FIFO
     */
    public static function moveFromInventoryForPos(array $allocations, $item, PosSale $sale): void
    {
        $movementDate = $sale->sale_date ?? now();

        foreach ($allocations as $alloc) {
            InventoryTransaction::create([
                'product_id'            => $item->product_id,
                'movement_type'         => InventoryTransaction::MOVEMENT_OUT,
                'quantity'              => $alloc['deducted_qty'],
                'unit_id'               => $alloc['target_unit_id'],
                'package_size'          => $alloc['target_unit_package_size'],
                'price'                 => $alloc['price_based_on_unit'],
                'movement_date'         => $movementDate,
                'transaction_date'      => $movementDate,

                // لو FIFO يرجع store_id نستخدمه، وإلا ن fallback لمخزن السند
                'store_id'              => $sale->store_id,

                'notes'                 => $alloc['notes'] ?? "POS Sale #{$sale->id}",

                'transactionable_id'    => $sale->id,
                'transactionable_type'  => self::class,

                'source_transaction_id' => $alloc['transaction_id'] ?? null,

                // لو عندك منطق base_unit في FIFO تقدر تضيفه هنا:
                'remaining_quantity'      => 0,
                'base_unit_id'            => $alloc['base_unit_id']      ?? $item->unit_id,
                'base_quantity'           => $alloc['base_quantity']     ?? $item->quantity,
                'base_unit_package_size'  => $alloc['base_package_size'] ?? ($item->package_size ?? 1),
                'price_per_base_unit'     => $alloc['price_per_unit']    ?? $alloc['price_based_on_unit'] ?? 0,
            ]);
        }
    }
}
