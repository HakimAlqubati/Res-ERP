<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class StockInventoryDetail extends Model implements Auditable

{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'stock_inventory_id',
        'product_id',
        'unit_id',
        'system_quantity',
        'physical_quantity',
        'difference',
        'package_size',
        'is_adjustmented',
    ];
    protected $auditInclude = [
        'stock_inventory_id',
        'product_id',
        'unit_id',
        'system_quantity',
        'physical_quantity',
        'difference',
        'package_size',
        'is_adjustmented',
    ];

    public function inventory()
    {
        return $this->belongsTo(StockInventory::class, 'stock_inventory_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The StockAdjustmentDetail records that were generated from this inventory detail.
     * Matched by source_id = stock_inventory_id, source_type = StockInventory,
     * and same product_id + unit_id.
     */
    public function adjustmentDetails()
    {
        return $this->hasMany(StockAdjustmentDetail::class, 'source_id', 'stock_inventory_id')
            ->where('source_type', StockInventory::class)
            ->where('product_id', $this->product_id)
            ->where('unit_id', $this->unit_id);
    }

    /**
     * Inventory transactions created from adjustments linked to this inventory detail.
     */
    public function inventoryTransactions()
    {
        return $this->hasManyThrough(
            InventoryTransaction::class,
            StockAdjustmentDetail::class,
            'source_id',          // FK on StockAdjustmentDetail
            'transactionable_id', // FK on InventoryTransaction
            'stock_inventory_id', // local key on StockInventoryDetail
            'id'                  // local key on StockAdjustmentDetail
        )
        ->where('stock_adjustment_details.source_type', StockInventory::class)
        ->where('stock_adjustment_details.product_id', $this->product_id)
        ->where('stock_adjustment_details.unit_id', $this->unit_id)
        ->where('inventory_transactions.transactionable_type', StockAdjustmentDetail::class);
    }
}
