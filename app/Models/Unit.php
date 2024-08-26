<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Support\Traits\Localizable;
// use Spatie\Translatable\HasTranslations;

class Unit extends Model
{
    use HasFactory, SoftDeletes
    // , HasTranslations, Localizable
    ;
    protected $fillable = [
        'name',
        'code',
        'description',
        'active'
    ];
    // public $translatable = [
    //     'name',
    //     'description',
    // ];

    // public $localizable = [
    //     'name',
    //     'description',
    // ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'unit_prices')
            ->withPivot('price');
    }
}
