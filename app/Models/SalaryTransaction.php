<?php
namespace App\Models;

use App\Enums\HR\Payroll\SalaryTransactionSubType;
use App\Enums\HR\Payroll\SalaryTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'hr_salary_transactions';
 

    const OPERATION_ADD = '+';
    const OPERATION_SUB = '-';

    // --- حالة الحركة (status) ---
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
        'year'   => 'integer',
        'month'  => 'integer',
        // 'type'      => SalaryTransactionType::class,     // Enum رئيسي
        // 'sub_type'  => SalaryTransactionSubType::class,
    ];

    
    // --- العملة الافتراضية (يمكن تعديلها حسب نظامك) ---
    public static function defaultCurrency()
    {
        return getDefaultCurrency();
    }
    protected $fillable = [
        'employee_id', 'payroll_id', 'date', 'amount', 'currency', 'type','sub_type',
        'reference_id', 'reference_type', 'description', 'created_by',
        'status', 'operation', 'year','month','payroll_run_id'
    ];
    // العلاقات
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function run()
{
    return $this->belongsTo(\App\Models\PayrollRun::class, 'payroll_run_id');
}

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