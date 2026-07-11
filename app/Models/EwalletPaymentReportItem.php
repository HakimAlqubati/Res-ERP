<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EwalletPaymentReportItem extends Model
{
    protected $table = 'hr_ewallet_payment_report_items';

    protected $fillable = [
        'hr_ewallet_payment_report_id',
        'payroll_id',
        'employee_id',
        'account_number',
        'net_salary',
        'reward_name',
        'reward_description',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(EwalletPaymentReport::class, 'hr_ewallet_payment_report_id');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
