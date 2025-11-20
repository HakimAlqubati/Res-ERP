<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acc_journal_entries';

    // Statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';

    protected $fillable = [
        'entry_date',
        'reference_number',
        'reference_type',
        'description',
        'branch_id',
        'status',
        'currency_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    // Assuming a Branch model exists, based on migration 'branch_id'
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
