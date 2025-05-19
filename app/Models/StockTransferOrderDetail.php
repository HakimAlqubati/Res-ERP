<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class StockTransferOrderDetail extends Model implements Auditable
{
    use HasFactory,\OwenIt\Auditing\Auditable;

    protected $fillable = [
        'stock_transfer_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'package_size',
        'note',
    ];
    protected $auditInclude = [
        'stock_transfer_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'package_size',
        'note',
    ];

    /**
     * Relationships
     */
    public function stockTransferOrder()
    {
        return $this->belongsTo(StockTransferOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
