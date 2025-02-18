<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_date',
        'store_id',
        'responsible_user_id',
        'finalized',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function details()
    {
        return $this->hasMany(StockInventoryDetail::class, 'stock_inventory_id');
    }
}
