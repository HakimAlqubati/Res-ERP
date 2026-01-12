<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acc_bank_accounts';

    protected $fillable = [
        'name',
        'account_number',
        'iban',
        'currency_id',
        'gl_account_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the currency of this bank account
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the GL (General Ledger) control account
     * This is the account in the Chart of Accounts (e.g., "Cash at Banks")
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    /**
     * Get all journal entry lines related to this bank account
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'bank_account_id');
    }

    /**
     * Scope to get only active bank accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted account display name
     */
    public function getFullNameAttribute(): string
    {
        return $this->name . ' (' . $this->account_number . ')';
    }
}
