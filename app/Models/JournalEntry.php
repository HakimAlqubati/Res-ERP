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
        'exchange_rate',
        'entry_number',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exchange_rate' => 'decimal:6',
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

    /**
     * Boot method to protect posted entries from modification
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent updating posted entries
        static::updating(function ($entry) {
            if ($entry->getOriginal('status') === self::STATUS_POSTED) {
                throw new \Exception('Cannot update a posted journal entry. Please reverse it first.');
            }
        });

        // Prevent deleting posted entries
        static::deleting(function ($entry) {
            if ($entry->status === self::STATUS_POSTED) {
                throw new \Exception('Cannot delete a posted journal entry. Please reverse it first.');
            }
        });
    }
}
