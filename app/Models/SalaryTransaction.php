<?php

namespace App\Models;

use App\Enums\HR\Payroll\SalaryTransactionSubType;
use App\Enums\HR\Payroll\SalaryTransactionType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ReflectionClass;

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
        'qty'         => 'decimal:2',
        'rate'        => 'decimal:4',
        'multiplier'  => 'decimal:3',
        // 'type'      => SalaryTransactionType::class,     // Enum رئيسي
        // 'sub_type'  => SalaryTransactionSubType::class,
    ];


    // --- العملة الافتراضية (يمكن تعديلها حسب نظامك) ---
    public static function defaultCurrency()
    {
        return getDefaultCurrency();
    }
    protected $fillable = [
        'employee_id',
        'payroll_id',
        'date',
        'amount',
        'currency',
        'type',
        'sub_type',
        'reference_id',
        'reference_type',
        'description',
        'notes',
        'effective_percentage',
        'created_by',
        'status',
        'operation',
        'year',
        'month',
        'payroll_run_id',
        'qty',
        'rate',
        'multiplier',
        'unit',
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

    public static function typeOptions(): array
    {
        return collect(SalaryTransactionType::cases())
            ->mapWithKeys(fn($case) => [$case->value => __(str_replace('TYPE_', '', $case->name))])
            ->toArray();
    }
    public static function subTypesForType(?string $type = null): array
    {
        if (!$type) {
            return [];
        }

        return collect(SalaryTransactionSubType::cases())
            ->filter(fn($case) => $case->parentType()->value === $type)
            ->mapWithKeys(fn($case) => [$case->value => __($case->name)])
            ->toArray();
    }
    protected static function booted()
    {
        static::creating(function ($transaction) {
            if ($transaction->payroll_id) {
                $payroll = \App\Models\Payroll::find($transaction->payroll_id);

                if ($payroll) {
                    if (!$transaction->payroll_run_id) {
                        $transaction->payroll_run_id = $payroll->payroll_run_id;
                    }

                    if (!$transaction->year) {
                        $transaction->year = $payroll->year;
                    }

                    if (!$transaction->month) {
                        $transaction->month = $payroll->month;
                    }
                }
            }
        });
    }


    public static function operationForType(string $type): string
    {
        return match ($type) {
            SalaryTransactionType::TYPE_DEDUCTION->value,
            SalaryTransactionType::TYPE_PENALTY->value,
            SalaryTransactionType::TYPE_ADVANCE->value,
            SalaryTransactionType::TYPE_INSTALL->value
            => self::OPERATION_SUB,

            default => self::OPERATION_ADD,
        };
    }
}
