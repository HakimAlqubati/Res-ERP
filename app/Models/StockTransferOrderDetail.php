<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class StockTransferOrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
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
