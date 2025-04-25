<?php

namespace App\Models;

use App\Services\UnitPriceSyncService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class UnitPrice extends Model implements Auditable

{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $table = 'unit_prices';
    public $primaryKey = 'id';
    protected $fillable = ['unit_id', 'product_id', 'price', 'package_size', 'order', 'minimum_quantity'];
    protected $auditInclude = ['unit_id', 'product_id', 'price', 'package_size', 'order', 'minimum_quantity'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function toArray()
    {
        return [
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit->name,
            'price' => $this->price,
            'package_size' => $this->package_size,
            'order' => $this->order,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $unitPrice) {
            // Automatically update package sizes in related tables
            UnitPriceSyncService::syncPackageSizeForProduct($unitPrice->product_id);
        });
    }
}
