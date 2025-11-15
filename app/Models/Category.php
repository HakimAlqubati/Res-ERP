<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

use Arcanedev\Support\Providers\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'for_pos',
        'parent_id',
    ];
    protected $auditInclude = [
        'name',
        'code',
        'description',
        'active',
        'is_manafacturing',
        'code_starts_with',
        'waste_stock_percentage',
        'for_pos',
        'parent_id',
    ];

    protected $casts = [
        'for_pos'            => 'boolean', // NEW 
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        // Self-referencing relationship: a category belongs to a parent category
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the children categories.
     */
    public function children(): HasMany
    {
        // Self-referencing relationship: a category has many child categories
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function toArray()
    {
        return [
            'category_id' => $this->id,
            'category_name' => $this->name,
            'is_manafacturing' => $this->is_manafacturing,
            'parent_id' => $this->parent_id,
            'parent_name' => $this->parent->name ?? null,
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

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_category', 'category_id', 'branch_id');
    }
    public function getBranchNamesAttribute(): string
    {
        return $this->branches->pluck('name')->implode(', ');
    }

    /** Scope: categories to show in POS */
    public function scopeForPos(Builder $q)
    {
        return $q->where('for_pos', true);
    }

    /** 
     * Scope: categories not shown in POS
     */
    public function scopeNotForPos(Builder $query)
    {
        return $query->where('for_pos', false);
    }

    /**
     * Scope: Only root categories (categories that do not have a parent)
     */
    public function scopeRoot(Builder $query)
    {
        return $query->whereNull('parent_id');
    }
}
