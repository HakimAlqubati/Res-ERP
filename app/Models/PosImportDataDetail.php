<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosImportDataDetail extends Model
{
    use SoftDeletes;

    protected $table = 'pos_import_data_details';

    protected $fillable = [
        'pos_import_data_id',
        'product_id',
        'unit_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
    ];

    public function header()
    {
        return $this->belongsTo(PosImportData::class, 'pos_import_data_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(\App\Models\Unit::class);
    }
}
