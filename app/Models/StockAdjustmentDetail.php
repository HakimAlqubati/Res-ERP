<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class StockAdjustmentDetail extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'unit_id',
        'package_size',
        'quantity',
        'notes',
        'adjustment_type',
        'created_by',
        'adjustment_date',
        'store_id',
        'reason_id',
        'source_id',
        'source_type'
    ];
    protected $auditInclude = [
        'stock_adjustment_id',
        'product_id',
        'unit_id',
        'package_size',
        'quantity',
        'notes',
        'adjustment_type',
        'created_by',
        'adjustment_date',
        'store_id',
        'reason_id',
    ];

    // Constants for adjustment types
    const ADJUSTMENT_TYPE_INCREASE = 'increase';
    const ADJUSTMENT_TYPE_DECREASE = 'decrease';
    const ADJUSTMENT_TYPE_EQUAL = 'equal';

    /**
     * Relationships
     */
    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}