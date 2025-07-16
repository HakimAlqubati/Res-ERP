<?php

// app/Models/HrMonthClosure.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthClosure extends Model
{
    protected $table = 'hr_month_closures';

    protected $fillable = [
        'year',
        'month',
        'status',
        'closed_at',
        'closed_by',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'closed_at' => 'datetime',
    ];

     // ENUM STATUSES
    public const STATUS_CLOSED   = 'closed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_OPEN     = 'open';
    public const STATUS_PENDING  = 'pending';

    public const STATUSES = [
        self::STATUS_CLOSED,
        self::STATUS_APPROVED,
        self::STATUS_OPEN,
        self::STATUS_PENDING,
    ];

}