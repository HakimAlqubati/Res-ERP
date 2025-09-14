<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    use SoftDeletes;

    protected $table = 'hr_payroll_runs';

    protected $fillable = [
        'branch_id',
        'year',
        'month',
        'name',
        'period_start_date',
        'period_end_date',
        'status',
        'currency',
        'fx_rate',
        'total_gross',
        'total_net',
        'total_allowances',
        'total_deductions',
        'created_by',
        'approved_by',
        'approved_at'
    ];
    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_APPROVED  = 'approved';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING   => __('Pending'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_APPROVED  => __('Approved'),
        ];
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'payroll_run_id');
    }
    public function transactions()
    {
        return $this->hasMany(SalaryTransaction::class, 'payroll_run_id');
    }
}
