<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAnalysisAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'user_id',
        'provider',
        'file_name',
        'status',
        'payload',
        'mapped_data',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'mapped_data' => 'array',
    ];

    /**
     * Get the parent documentable model (e.g., GoodsReceivedNote, PurchaseInvoice).
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
