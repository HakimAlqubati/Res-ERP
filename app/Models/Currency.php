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
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
