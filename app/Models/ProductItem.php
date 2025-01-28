<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'total_price',
        'parent_product_id',
    ];

    /**
     * Define the relationship to the Product model.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Define the relationship to the Unit model.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
