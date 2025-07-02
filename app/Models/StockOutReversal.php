<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockOutReversal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reversed_type',
        'reversed_id',
        'store_id',
        'reason',
        'created_by',
    ];

    protected static function booted()
    {
        static::creating(function ($reversal) {
            $reversal->created_by = auth()->id();
        });
    }

    public function reversed(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reversed_type', 'reversed_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'transactionable');
    }
}