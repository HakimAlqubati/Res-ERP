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
        'qty_waste_percentage',
        'total_price_after_waste',
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

    /**
     * Get the total price after applying waste percentage.
     * 
     * @return float
     */
    public function getTotalPriceAfterWasteAttribute()
    {
        $wasteAmount = ($this->qty_waste_percentage / 100) * $this->total_price;
        return round($this->total_price - $wasteAmount, 2);
    }

    /**
     * Automatically update total_price_after_waste before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($productItem) {
            $productItem->total_price_after_waste = $productItem->total_price_after_waste;
        });
    }
}
