<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitPrice extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'unit_prices';
    public $primaryKey = 'id';
    protected $fillable = ['unit_id', 'product_id', 'price'];
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
        ];
    }
}
