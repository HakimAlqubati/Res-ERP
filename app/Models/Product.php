<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Translatable\HasTranslations;

class Product extends Model implements Auditable
{
    use HasFactory,
        SoftDeletes,
        \OwenIt\Auditing\Auditable
        // , HasTranslations
    ;
    // public $translatable = ['name', 'description'];

    protected $fillable = [
        'id',
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
        'waste_stock_percentage',
    ];
    protected $auditInclude = [
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
        'waste_stock_percentage',
    ];
    protected $appends = ['unit_prices_count', 'product_items_count', 'is_manufacturing', 'formatted_unit_prices'];

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

    /**
     * Get the count of product items for the product.
     *
     * @return int
     */
    public function getProductItemsCountAttribute()
    {
        return $this->productItems()->count();
    }

    /**
     * Check if the product belongs to a manufacturing category.
     *
     * @return bool
     */
    public function getIsManufacturingAttribute()
    {
        return (bool) optional($this->category)->is_manafacturing;
    }

    /**
     * Get unit prices as a comma-separated string.
     *
     * @return string
     */
    public function getFormattedUnitPricesAttribute()
    {
        return $this->unitPrices->map(function ($unitPrice) {
            return "{$unitPrice->unit->name} : {$unitPrice->price}";
        })->implode(', ');
    }

    public static function generateProductCode($categoryId): string
    {
        $category = \App\Models\Category::find($categoryId);
        if (!$category || !$category->code_starts_with) {
            return '';
        }

        $prefix = $category->code_starts_with;

        // Get latest product with this prefix
        $lastProduct = static::where('category_id', $categoryId)
            ->where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastProduct) {
            $lastCode = (int)substr($lastProduct->code, strlen($prefix));
            $nextNumber = $lastCode + 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
    public function productPriceHistories()
    {
        return $this->hasMany(ProductPriceHistory::class, 'product_id');
    }
}
