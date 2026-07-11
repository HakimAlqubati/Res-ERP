<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EwalletPaymentReport extends Model
{
    protected $table = 'hr_ewallet_payment_reports';

    protected $fillable = [
        'month',
        'year',
        'total_amount',
        'employees_count',
        'status',
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
