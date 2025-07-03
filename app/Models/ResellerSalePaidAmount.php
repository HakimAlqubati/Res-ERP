<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResellerSalePaidAmount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reseller_sale_id',
        'amount',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    // ✅ علاقة الفاتورة
    public function resellerSale()
    {
        return $this->belongsTo(ResellerSale::class);
    }

    // ✅ الشخص الذي أضاف الدفعة
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ✅ علاقة غير مباشرة للفرع
    public function branch()
    {
        return $this->hasOneThrough(
            Branch::class,
            ResellerSale::class,
            'id', // ResellerSale: id
            'id', // Branch: id
            'reseller_sale_id', // ResellerSalePaidAmount: FK
            'branch_id' // ResellerSale: FK
        );
    }
}