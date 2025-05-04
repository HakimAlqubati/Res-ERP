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

    protected static function boot()
    {
        parent::boot();

        static::created(function ($grnDetail) {
            if (!settingWithDefault('grn_affects_inventory', false)) {
                return;
            }

            $grn = $grnDetail->grn;
            $notes = 'GRN with id ' . $grn?->id;
            if ($grn?->store?->name) {
                $notes .= ' in (' . $grn->store->name . ')';
            }

            \App\Models\InventoryTransaction::create([
                'product_id' => $grnDetail->product_id,
                'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_IN,
                'quantity' => $grnDetail->quantity,
                'package_size' => $grnDetail->package_size,
                'price' => getUnitPrice($grnDetail->product_id, $grnDetail->unit_id),
                'movement_date' => $grn->grn_date ?? now(),
                'unit_id' => $grnDetail->unit_id,
                'store_id' => $grn->store_id,
                'notes' => $notes,
                'transaction_date' => $grn->grn_date ?? now(),
                'transactionable_id' => $grn->id,
                'transactionable_type' => \App\Models\GoodsReceivedNote::class,
            ]);
        });
    }
}
