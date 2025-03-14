<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

// use Illuminate\Support\Traits\Localizable;
// use Spatie\Translatable\HasTranslations;

class Unit extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable
        // , HasTranslations, Localizable
    ;
    protected $fillable = [
        'name',
        'code',
        'description',
        'active',
        'parent_unit_id',
        'conversion_factor',
        'operation',
    ];
    protected $auditInclude = [
        'name',
        'code',
        'description',
        'active',
        'parent_unit_id',
        'conversion_factor',
        'operation',
    ];

    protected $appends = ['is_main'];
    /**
     * The available operations for the `operation` field.
     */
    public const OPERATIONS = [
        '*' => '*',
        '/'   => '/',
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

    public function productItems()
    {
        return $this->hasMany(ProductItem::class);
    }

    public function parent()
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id');
    }

    public function children()
    {
        return $this->hasMany(Unit::class, 'parent_unit_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the parent and all nested children recursively with their IDs and names.
     *
     * @return array
     */
    public function getParentAndChildrenWithNested()
    {
        $result = [];

        // Add the current unit (parent) to the result
        $result[] = [
            'id' => $this->id,
            'name' => $this->name,
            'conversion_factor' => $this->conversion_factor,
            'operation' => $this->operation,
        ];

        // Fetch children recursively
        foreach ($this->children as $child) {
            $result = array_merge($result, $child->getParentAndChildrenWithNested());
        }

        return $result;
    }

    public function getIsMainAttribute()
    {
        return is_null($this->parent_unit_id);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_unit_id');
    }
}
