<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryDetail extends Model
{
    use HasFactory;

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
 
}
