<?php

namespace App\Traits\Inventory;

use App\Models\Product;
use App\Models\Unit;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\StockSupplyOrder;

trait InventoryRelations
{
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}