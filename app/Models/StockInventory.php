<?php

namespace App\Models;

use App\Services\Financial\ClosingStockCalculationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class StockInventory extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    public const TYPE_ZEROING = 'zeroing';
    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'inventory_date',
        'store_id',
        'responsible_user_id',
        'finalized',
        'created_by',
        'inventory_type',
    ];
    protected $auditInclude = [
        'inventory_date',
        'store_id',
        'responsible_user_id',
        'finalized',
        'created_by',
        'inventory_type',
    ];

    protected $appends = ['details_count', 'categories_names', 'closing_stock_value'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function details()
    {
        return $this->hasMany(StockInventoryDetail::class, 'stock_inventory_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($stockSupplyOrder) {
            $stockSupplyOrder->created_by = auth()->id();
        });
    }

    public function getDetailsCountAttribute()
    {
        return $this->details()->count();
    }

    public function getCategoriesNamesAttribute()
    {
        return $this->details()
            ->with('product.category')
            ->get()
            ->pluck('product.category.name')
            ->filter() // يستبعد null
            ->unique()
            ->implode(', ');
    }

    public function getClosingStockValueAttribute(): float
    {
        return app(ClosingStockCalculationService::class)->calculateClosingStockValue($this);
    }

    public function isZeroing()
    {
        return $this->inventory_type === self::TYPE_ZEROING;
    }

    public function scopeZeroing($query)
    {
        return $query->where('inventory_type', self::TYPE_ZEROING);
    }

    public function stockAdjustmentDetails()
    {
        return $this->hasMany(StockAdjustmentDetail::class, 'source_id')
            ->where('source_type', self::class);
    }

    public function getLinkedOutboundTransactions()
    {
        $adjDetailIds = StockAdjustmentDetail::withTrashed()
            ->where('source_id', $this->id)
            ->where('source_type', self::class)
            ->pluck('id');

        if ($adjDetailIds->isEmpty()) {
            return collect();
        }

        $inboundTxIds = InventoryTransaction::withTrashed()
            ->whereIn('transactionable_id', $adjDetailIds)
            ->where('transactionable_type', StockAdjustmentDetail::class)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->pluck('id');

        if ($inboundTxIds->isEmpty()) {
            return collect();
        }

        return InventoryTransaction::whereIn('source_transaction_id', $inboundTxIds)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->with(['product:id,name,code'])
            ->get();
    }

    public function hasOutboundTransactions(): bool
    {
        return $this->getLinkedOutboundTransactions()->isNotEmpty();
    }
}
