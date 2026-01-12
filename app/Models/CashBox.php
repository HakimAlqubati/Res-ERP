<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashBox extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acc_cash_boxes';

    protected $fillable = [
        'name',
        'currency_id',
        'gl_account_id',
        'keeper_id',
        'max_limit',
        'is_active',
    ];

    protected $casts = [
        'max_limit' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the currency of this cash box
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the GL (General Ledger) control account
     * This is the account in the Chart of Accounts (e.g., "Cash on Hand", "Petty Cash")
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    /**
     * Get the employee responsible for this cash box
     */
    public function keeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'keeper_id');
    }

    /**
     * Get all journal entry lines related to this cash box
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'cash_box_id');
    }

    /**
     * Scope to get only active cash boxes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if cash box is at or above max limit
     */
    public function isAtMaxLimit(): bool
    {
        if ($this->max_limit <= 0) {
            return false;
        }

        $currentBalance = $this->getCurrentBalance();
        return $currentBalance >= $this->max_limit;
    }

    /**
     * Calculate current balance from journal lines
     */
    public function getCurrentBalance(): float
    {
        return $this->journalLines()
            ->selectRaw('SUM(debit - credit) as balance')
            ->value('balance') ?? 0;
    }
}
