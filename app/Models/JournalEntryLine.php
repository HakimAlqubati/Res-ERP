<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acc_journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'debit_foreign',
        'credit_foreign',
        'cost_center_id',
        'branch_id',
        'line_description',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'debit_foreign' => 'decimal:4',
        'credit_foreign' => 'decimal:4',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // Assuming Branch model exists
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Assuming CostCenter model exists (not created in this task, but referenced in migration)
    // If it doesn't exist yet, I'll comment it out or assume it might be created later.
    // For now, I will add the relationship assuming standard naming if it were to exist, or just leave it.
    // Given the prompt didn't ask for CostCenter model, I'll leave the relationship method but maybe comment it if I'm unsure, 
    // but usually it's safe to add.
    /*
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
    */
}
