<?php

namespace App\Models;

use App\Traits\Inventory\HasStockTransferInventoryTransactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;

class StockTransferOrder extends Model  implements Auditable
{
    use HasFactory, SoftDeletes, HasStockTransferInventoryTransactions, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'from_store_id',
        'to_store_id',
        'date',
        'status',
        'created_by',
        'rejected_by',
        'rejected_reason',
        'notes',
    ];
    protected $auditInclude = [
        'from_store_id',
        'to_store_id',
        'date',
        'status',
        'created_by',
        'rejected_by',
        'rejected_reason',
        'notes',
    ];

    /**
     * Status Constants
     */
    const STATUS_CREATED  = 'created';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Status List
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_CREATED  => 'Created',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /**
     * Relationships
     */
    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function details()
    {
        return $this->hasMany(StockTransferOrderDetail::class);
    }

    protected static function booted()

    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });
    }

    public function getDetailsCountAttribute()
    {
        return $this->details()->count();
    }
}
