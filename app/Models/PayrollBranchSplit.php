<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PayrollBranchSplit
 *
 * طبقة التوثيق المحاسبي لتوزيع عبء الراتب بين الفروع
 * عند انتقال الموظف خلال فترة الراتب.
 *
 * لا تؤثر على حساب الراتب — تُقرأ فقط في المزامنة المالية
 * لتحديد الفرع الصحيح لكل جزء من تكلفة الراتب.
 */
class PayrollBranchSplit extends Model
{
    protected $table = 'hr_payroll_branch_splits';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'branch_id',
        'from_date',
        'to_date',
        'days_count',
        'total_days',
        'ratio',
        'allocated_amount',
        'liability_mode',
    ];

    protected $casts = [
        'from_date'        => 'date',
        'to_date'          => 'date',
        'ratio'            => 'decimal:4',
        'allocated_amount' => 'decimal:2',
        'days_count'       => 'integer',
        'total_days'       => 'integer',
    ];

    // ─────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────

    public function payroll(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
