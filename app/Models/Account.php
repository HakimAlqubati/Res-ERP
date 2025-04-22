<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'code', 'type', 'parent_id'];
    public const TYPE_ASSET     = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY    = 'equity';
    public const TYPE_REVENUE   = 'revenue';
    public const TYPE_EXPENSE   = 'expense';

    protected $casts = [
        'type' => 'string',
    ];

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_ASSET     => 'Assets',
            self::TYPE_LIABILITY => 'Liabilities',
            self::TYPE_EQUITY    => 'Lquity',
            self::TYPE_REVENUE   => 'Revenue',
            self::TYPE_EXPENSE   => 'Expenses',
        ];
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function scopeAssets($query)
    {
        return $query->where('type', self::TYPE_ASSET);
    }

    public function scopeLiabilities($query)
    {
        return $query->where('type', self::TYPE_LIABILITY);
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    // الفروع المرتبطة بهذا الحساب كمصاريف تشغيل
    public function branches()
    {
        return $this->hasMany(Branch::class, 'operational_cost_account_id');
    }

    // المخازن المرتبطة بهذا الحساب كمخزون
    public function stores()
    {
        return $this->hasMany(Store::class, 'inventory_account_id');
    }

    // الموردين المرتبطين بهذا الحساب
    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'account_id');
    }

    public static function generateNextCode(?int $parentId = null): string
    {
        // If parent ID is provided, prefix based on parent
        if ($parentId) {
            $parent = self::find($parentId);
            $prefix = $parent?->code ?? '';

            // Get last child code
            $lastChild = self::where('parent_id', $parentId)
                ->where('code', 'like', "$prefix.%")
                ->orderBy('code', 'desc')
                ->first();

            $nextNumber = 1;
            if ($lastChild) {
                $parts = explode('.', $lastChild->code);
                $lastSegment = (int) end($parts);
                $nextNumber = $lastSegment + 1;
            }

            return "$prefix." . $nextNumber;
        }

        // If no parent, generate top-level code
        $lastTopLevel = self::whereNull('parent_id')
            ->whereRaw('code REGEXP "^[0-9]+$"')
            ->orderByRaw('CAST(code AS UNSIGNED) desc')
            ->first();

        $next = $lastTopLevel ? ((int) $lastTopLevel->code + 1) : 1;

        return (string) $next;
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }
}
