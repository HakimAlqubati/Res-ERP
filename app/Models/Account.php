<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acc_accounts';

    // Account Types
    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY = 'equity';
    const TYPE_REVENUE = 'revenue';
    const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'is_parent',
        'parent_id',
        'is_active',
        'allow_manual_entries',
        'currency_id',
    ];

    protected $casts = [
        'is_parent' => 'boolean',
        'is_active' => 'boolean',
        'allow_manual_entries' => 'boolean',
    ];

    /**
     * Get account types with bilingual labels
     * 
     * @return array<string, string>
     */
    public static function getAccountTypes(): array
    {
        return [
            self::TYPE_ASSET => 'أصول - Assets',
            self::TYPE_LIABILITY => 'التزامات - Liabilities',
            self::TYPE_EQUITY => 'حقوق الملكية - Equity',
            self::TYPE_REVENUE => 'إيرادات - Revenue',
            self::TYPE_EXPENSE => 'مصروفات - Expenses',
        ];
    }

    /**
     * Get account type label by key
     * 
     * @param string|null $type
     * @return string
     */
    public static function getAccountTypeLabel(?string $type): string
    {
        return self::getAccountTypes()[$type] ?? $type ?? '';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }
}
