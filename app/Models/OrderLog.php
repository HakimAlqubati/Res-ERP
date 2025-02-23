<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    use HasFactory;

    protected $table = 'order_logs';

    protected $fillable = [
        'order_id',
        'created_by',
        'log_type', // 'created', 'updated', 'change_status'
        'message',
        'new_status',
    ];

    public const TYPE_CREATED = 'created';
    public const TYPE_UPDATED = 'updated';
    public const TYPE_CHANGE_STATUS = 'change_status';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
