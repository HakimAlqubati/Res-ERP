<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPaidAmount extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'amount',
        'paid_at',
        'notes',
        'created_by',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}


