<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCategory extends Model
{
    use SoftDeletes;

    // Constants for category types
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';

    const TYPES = [
        self::TYPE_INCOME => 'Income',
        self::TYPE_EXPENSE => 'Expense',
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'parent_id',
        'is_system',
        'is_visible',
        'description',
        'code',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_visible' => 'boolean',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'category_id');
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public static function findByCode($code)
    {
        return self::byCode($code)->first();
    }
}
