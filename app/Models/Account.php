<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'code',
        'type',
        'parent_id',
        'normal_balance',
        'is_parent',
    ];
    public const TYPE_ASSET     = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY    = 'equity';
    public const TYPE_REVENUE   = 'revenue';
    public const TYPE_EXPENSE   = 'expense';

    public const NORMAL_BALANCE_DEBIT = 'debit';
    public const NORMAL_BALANCE_CREDIT = 'credit';

    protected $casts = [
        'type' => 'string',
        'normal_balance' => 'string',
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



    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function getNormalBalanceAttribute()
    {
        return match ($this->type) {
            self::TYPE_ASSET, self::TYPE_EXPENSE => self::NORMAL_BALANCE_DEBIT,
            self::TYPE_LIABILITY, self::TYPE_EQUITY, self::TYPE_REVENUE => self::NORMAL_BALANCE_CREDIT,
            default => null,
        };
    }

    public function getFinancialStatementAttribute(): ?string
    {
        return match ($this->type) {
            self::TYPE_ASSET, self::TYPE_LIABILITY, self::TYPE_EQUITY => 'Balance Sheet',
            self::TYPE_REVENUE, self::TYPE_EXPENSE => 'Income Statement',
            default => null,
        };
    }

    public function scopeRevenues($query)
    {
        return $query->where('type', self::TYPE_REVENUE);
    }

    public function scopeEquities($query)
    {
        return $query->where('type', self::TYPE_EQUITY);
    }
    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    public function scopeFinalAccounts($query)
    {
        return $query->whereIn('type', [self::TYPE_REVENUE, self::TYPE_EXPENSE]);
    }

    public function isMainAccount(): bool
    {
        return $this->is_parent;
    }

    public function getFormattedCodeAttribute(): string
    {
        if (!$this->parent) {
            return (string) $this->code;
        }

        $segments = [];
        $current = $this;
        while ($current) {
            array_unshift($segments, $current->code % 100); // آخر خانتين
            $current = $current->parent;
        }

        return implode('.', $segments);
    }
    
}
