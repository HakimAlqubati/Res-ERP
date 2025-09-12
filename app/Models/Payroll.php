<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use SoftDeletes;

    protected $table = 'hr_payrolls';

    // الثوابت
    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_PAID      = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'year',
        'month',
        'period_start_date',
        'period_end_date',
        'base_salary',
        'total_allowances',
        'total_bonus',
        'overtime_amount',
        'total_deductions',
        'total_advances',
        'total_penalties',
        'total_insurance',
        'employer_share',
        'employee_share',
        'taxes_amount',
        'other_deductions',
        'gross_salary',
        'net_salary',
        'currency',
        'status',
        'pay_date',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'name',
        'payroll_run_id'
    ];

    /**
     * إرجاع جميع الحالات الممكنة
     *
     * @return array
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING   => __('Pending'),
            self::STATUS_APPROVED  => __('Approved'),
            self::STATUS_PAID      => __('Paid'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    // العلاقات
    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id', 'id')
            ->withoutGlobalScopes();
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

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // حركات الراتب المرتبطة (لو عندك ربط بالحركات المالية)
    public function transactions()
    {
        return $this->hasMany(SalaryTransaction::class, 'payroll_id');
    }
    public function run()
    {
        return $this->belongsTo(\App\Models\PayrollRun::class, 'payroll_run_id');
    }

    protected function netSalary(): Attribute
    {
        return Attribute::get(function () {
            $transactions = $this->transactions()
                ->where('status', SalaryTransaction::STATUS_APPROVED)
                ->get();

            $additions = $transactions->where('operation', SalaryTransaction::OPERATION_ADD)->sum('amount');
            $deductions = $transactions->where('operation', SalaryTransaction::OPERATION_SUB)->sum('amount');

            return $additions - $deductions;
        });
    }
}
