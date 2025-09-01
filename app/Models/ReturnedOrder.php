<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ReturnedOrder extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    const STATUS_CREATED  = 'created';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'original_order_id',
        'branch_id',
        'reason',
        'returned_date',
        'status',
        'approved_by',
        'created_by',
        'store_id',
    ];
    protected $auditInclude = [
        'original_order_id',
        'branch_id',
        'reason',
        'returned_date',
        'status',
        'approved_by',
        'created_by',
        'store_id',
    ];

    protected $appends = ['total_amount'];
    protected $casts = [
        'returned_date' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(ReturnedOrderDetail::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'original_order_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_CREATED  => 'Created',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function getTotalQuantityAttribute(): float
    {
        return $this->details->sum('quantity');
    }
    public function getItemsCountAttribute(): float
    {
        return $this->details->count();
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->details->sum(fn($d) => $d->quantity * $d->price);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function getTotalReturnedAmountAttribute(): float
    {
        return $this->returns()
            ->approved() // نأخذ فقط المرتجعات المعتمدة
            ->with('details') // eager load لتفاصيل المرتجع
            ->get()
            ->flatMap(function ($returnedOrder) {
                return $returnedOrder->details;
            })
            ->sum(function ($detail) {
                return $detail->quantity * $detail->price;
            });
    }

    public function getReturnedTotalAmountAttribute(): float
    {
        return $this->details->sum(fn($detail) => $detail->quantity * $detail->price);
    }

    public function toArray()
    {

        $array = parent::toArray(); // Get the default array representation

        // You can customize the response here
        // For example, you might want to format dates or add additional fields
        $array['total_amount'] = formatMoneyWithCurrency($this->total_amount);
        $array['formatted_returned_date'] = Carbon::parse($this->returned_date)->format('Y-m-d'); // Format the returned_date

        return $array;
    }
}