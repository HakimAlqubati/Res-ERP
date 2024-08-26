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
        //, HasTranslations
        ;
    public $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'code',
        'description',
        'active',
        'category_id',
        'product_code',
        'category_code',
    ];

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
}
