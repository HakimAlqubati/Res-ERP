<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acc_currencies';

    protected $fillable = [
        'currency_code',
        'currency_name',
        'symbol',
        'is_base',
        'exchange_rate',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'exchange_rate' => 'decimal:6',
    ];

    /**
     * Boot method to enforce single base currency
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($currency) {
            // If this currency is being set as base, unset all other base currencies
            if ($currency->is_base) {
                static::where('id', '!=', $currency->id)
                    ->update(['is_base' => false]);
            }
        });
    }
}
