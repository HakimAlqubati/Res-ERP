<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'total_price',
        'parent_product_id',
        'qty_waste_percentage',
        'total_price_after_waste',
        'package_size',
        'quantity_after_waste',
    ];
    protected $auditInclude = [
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'total_price',
        'parent_product_id',
        'qty_waste_percentage',
        'total_price_after_waste',
        'package_size',
        'quantity_after_waste',
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
     * Get the quantity after applying waste percentage.
     * 
     * @return float
     */
    public function getQuantityAfterWasteAttribute()
    {
        $wasteAmount = ($this->qty_waste_percentage / 100) * $this->quantity;
        return round($this->quantity + $wasteAmount, 2);
    }



    /**
     * Calculate total price after waste percentage.
     */
    public static function calculateTotalPriceAfterWaste(float $totalPrice, float $wastePercentage): float
    { 
        return round($totalPrice * (1 + ($wastePercentage / 100)), 8);
    }

    /**
     * Calculate quantity after waste percentage.
     */
    public static function calculateQuantityAfterWaste(float $quantity, float $wastePercentage): float
    {
        return round($quantity * (1 + ($wastePercentage / 100)), 2);
    }

    public function toArray()
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this?->product?->name ?? '',
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit->name??'',
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total_price' => $this->total_price,
            'parent_product_id' => $this->parent_product_id,
            'qty_waste_percentage' => $this->qty_waste_percentage,
            'total_price_after_waste' => $this->total_price_after_waste,
            'package_size' => $this->package_size,
            'quantity_after_waste' => $this->quantity_after_waste,
        ];
    }
}