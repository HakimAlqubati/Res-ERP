<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceivedNoteDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'package_size',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'double',
        'price' => 'double',
        'package_size' => 'double',
    ];

    // 🔗 العلاقات

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // 📦 حساب السعر الإجمالي للسطر
    public function getTotalAmountAttribute()
    {
        return $this->quantity * $this->price;
    }
}
