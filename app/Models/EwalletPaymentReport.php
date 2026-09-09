<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EwalletPaymentReport extends Model
{
    use SoftDeletes;
    protected $table = 'hr_ewallet_payment_reports';

    public const TYPE_EWALLET = 'ewallet';
    public const TYPE_BANK = 'bank';
    public const TYPE_CASH = 'cash';
    public const TYPE_CACHE = 'cash';

    protected $fillable = [
        'month',
        'year',
        'total_amount',
        'employees_count',
        'status',
        'payment_type',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(EwalletPaymentReportItem::class, 'hr_ewallet_payment_report_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
