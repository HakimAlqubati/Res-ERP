<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory,
        SoftDeletes
        // , HasTranslations
    ;
    // public $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'code',
        'description',
        'active',
        'category_id',
        'product_code',
        'category_code',
        'main_unit_id',
        'basic_price',
        'minimum_stock_qty',
    ];
    protected $appends = ['unit_prices_count'];

    /**
     * Scope to filter products with at least 2 unit prices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithMinimumUnitPrices($query, $count = 2)
    {
        return $query->withCount('unitPrices') // Count unitPrices
            ->having('unit_prices_count', '>=', $count); // Filter based on the count
    }
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'unit_prices')
            ->withPivot('price');
    }

    public function unitPrices()
    {
        return $this->hasMany(UnitPrice::class);
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function order_details()
    {
        return $this->hasMany(OrderDetails::class);
    }


    public function toArray()
    {
        return [
            'product_id' => $this->id,
            'product_name' => $this->name,
            'description' => $this->description,
            'unit_prices' => $this->unitPrices,
            'product_items' => $this->productItems,
        ];
    }
    //new code
    public function scopeActive($query)
    {
        return $query->where('active', '=', 1);
    }

    // to return products that have unit prices only
    public function scopeHasUnitPrices($query)
    {
        return $query->has('unitPrices');
    }

    public function scopeHasProductItems($query)
    {
        return $query->has('productItems');
    }

    public function productItems()
    {
        return $this->hasMany(ProductItem::class, 'parent_product_id');
    }

    // Scope to return products belonging to manufacturing categories
    public function scopemanufacturingCategory($query)
    {
        return $query->whereHas('category', function ($query) {
            $query->where('is_manafacturing', true);
        });
    }
    public function scopeUnmanufacturingCategory($query)
    {
        return $query->whereHas('category', function ($query) {
            $query->where('is_manafacturing', false);
        });
    }

    /**
     * Relation to the Unit model for the main unit.
     */
    public function mainUnit()
    {
        return $this->belongsTo(Unit::class, 'main_unit_id');
    }

    /**
     * Get the final price as the sum of 'total_price' from related ProductItems.
     *
     * @return float
     */
    public function getFinalPriceAttribute()
    {
        return $this->productItems->sum('total_price_after_waste');
    }

    /**
     * Get the count of unit prices for the product.
     *
     * @return int
     */
    public function getUnitPricesCountAttribute()
    {
        return $this->unitPrices()->count();
    }
}
