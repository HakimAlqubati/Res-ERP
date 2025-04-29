<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_item_id',
        'unit_id',
        'old_price',
        'new_price',
        'source_type',
        'source_id',
        'note',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function source() // morph relation
    {
        return $this->morphTo();
    }
}
