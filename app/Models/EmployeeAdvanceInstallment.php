<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAdvanceInstallment extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_advance_installments';

    protected $fillable = [
        'employee_id',
        'application_id',
        'installment_amount',
        'due_date',
        'is_paid',
        'paid_date',
        'sequence',
        'status',
        'paid_payroll_id',
    ];

    protected $casts = [
        'installment_amount' => 'decimal:2',
        'due_date'           => 'date',
        'paid_date'          => 'datetime',
        'is_paid'            => 'boolean',
    ];

    
    // ✅ ثوابت الحالات
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PAID      = 'paid';
    public const STATUS_SKIPPED   = 'skipped';
    public const STATUS_CANCELLED = 'cancelled';

    public static array $statuses = [
        self::STATUS_SCHEDULED,
        self::STATUS_PAID,
        self::STATUS_SKIPPED,
        self::STATUS_CANCELLED,
    ];
    // Relationship: Belongs to a single employee application
    public function application()
    {
        return $this->belongsTo(EmployeeApplicationV2::class, 'application_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'paid_payroll_id');
    }

    // ✅ سكوبات مساعدة
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopeDueInMonth($query, int $year, int $month)
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = Carbon::create($year, $month, 1)->endOfMonth();
        return $query->whereBetween('due_date', [$start, $end]);
    }

    // ✅ دالة تعليم القسط كمسدّد
    public function markPaid(?int $payrollId = null, ?\DateTimeInterface $when = null): void
    {
        $this->is_paid  = true;
        $this->status   = self::STATUS_PAID;
        $this->paid_payroll_id = $payrollId;
        $this->paid_date = $when ? Carbon::parse($when) : now();
        $this->save();
    }
}
