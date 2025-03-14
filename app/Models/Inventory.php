<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Inventory extends Model implements Auditable  
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'inventories';

    protected $fillable = [
        'product_id',
        'unit_id',
        'quantity',
        'last_updated',
    ];
    protected $auditInclude = [
        'product_id',
        'unit_id',
        'quantity',
        'last_updated',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
