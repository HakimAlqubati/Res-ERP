<?php

namespace App\Models;

use Arcanedev\Support\Providers\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Traits\Localizable;
use OwenIt\Auditing\Contracts\Auditable;

class Category extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable
        //  HasTranslations,Localizable
    ;
    public $translatable = ['name', 'description'];
    public $localizable = ['name'];
    protected $fillable = [
        'name',
        'code',
        'description',
        'active',
        'is_manafacturing',
        'code_starts_with',
        'waste_stock_percentage',
    ];
    protected $auditInclude = [
        'name',
        'code',
        'description',
        'active',
        'is_manafacturing',
        'code_starts_with',
        'waste_stock_percentage',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function toArray()
    {
        return [
            'category_id' => $this->id,
            'category_name' => $this->name,
            'products' => $this->products,
        ];
    }

    //new code
    public function scopeActive($query)
    {
        return $query->where('active', '=', 1);
    }

    // Scope to filter manufacturing categories
    public function scopeManufacturing($query)
    {
        return $query->where('is_manafacturing', '=', true);
    }
}
