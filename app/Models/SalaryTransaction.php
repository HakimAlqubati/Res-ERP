<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'hr_salary_transactions';

    const TYPE_SALARY    = 'salary';
    const TYPE_ALLOWANCE = 'allowance';
    const TYPE_DEDUCTION = 'deduction';
    const TYPE_ADVANCE   = 'advance';
    const TYPE_INSTALL   = 'installment';
    const TYPE_BONUS     = 'bonus';
    const TYPE_OVERTIME  = 'overtime';
    const TYPE_PENALTY   = 'penalty';
    const TYPE_OTHER     = 'other';

    const OPERATION_ADD = '+';
    const OPERATION_SUB = '-';

    // --- حالة الحركة (status) ---
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // --- العملة الافتراضية (يمكن تعديلها حسب نظامك) ---
    public static function defaultCurrency()
    {
        return getDefaultCurrency();
    }
    protected $fillable = [
        'employee_id', 'payroll_id', 'date', 'amount', 'currency', 'type',
        'reference_id', 'reference_type', 'description', 'created_by',
        'status', 'operation', 'year','month'
    ];
    // العلاقات
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // public function payroll()
    // {
    //     return $this->belongsTo(Payroll::class);
    // }

    // Morph relation للمرجع (خصم، سلفة، ...الخ)
    public function referenceable()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    // من أنشأ الحركة
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}