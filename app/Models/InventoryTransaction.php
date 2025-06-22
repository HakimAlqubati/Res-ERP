<?php

namespace App\Models;

use App\Traits\Inventory\InventoryAttributes;
use App\Traits\Inventory\InventoryBootEvents;
use App\Traits\Inventory\InventoryRelations;
use App\Traits\Inventory\InventoryStaticMethods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;

class InventoryTransaction extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable, InventoryRelations, InventoryAttributes, InventoryStaticMethods, InventoryBootEvents;
    // Table name
    protected $table = 'inventory_transactions';

    // Fillable fields
    protected $fillable = [
        'product_id',
        'movement_type',
        'quantity',
        'unit_id',
        'movement_date',
        'notes',
        'package_size',
        'store_id',
        'price',
        'transaction_date',
        'purchase_invoice_id',
        'transactionable_id',
        'transactionable_type',
        'waste_stock_percentage',
        'source_transaction_id',
    ];

    protected $auditInclude = [
        'product_id',
        'movement_type',
        'quantity',
        'unit_id',
        'movement_date',
        'notes',
        'package_size',
        'store_id',
        'price',
        'transaction_date',
        'purchase_invoice_id',
        'transactionable_id',
        'transactionable_type',
        'waste_stock_percentage',
        'source_transaction_id',
    ];
    protected $appends = ['movement_type_title', 'formatted_transactionable_type'];

    // Constant movement types
    const MOVEMENT_OUT = 'out';
    const MOVEMENT_IN = 'in';

    protected static function boot()
    {
        parent::boot();
        static::bootInventoryBootEvents();
    }

    public function sourceTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class, 'source_transaction_id');
    }

    public function getFormattedTransactionableTypeAttribute(): ?string
    {
        return class_basename($this->transactionable_type);
    }

    public function scopeByModelType($query, $modelClass)
    {
        return $query->where('transactionable_type', $modelClass);
    }
}
