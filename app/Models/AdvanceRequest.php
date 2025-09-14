<?php

namespace App\Models;

use Throwable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvanceRequest extends Model
{
    use HasFactory;

    protected $table = 'hr_advance_requests';

    protected $fillable = [
        'application_id',
        'application_type_id',
        'application_type_name',
        'employee_id',
        'advance_amount',
        'monthly_deduction_amount',
        'deduction_ends_at',
        'number_of_months_of_deduction',
        'date',
        'deduction_starts_from',
        'reason',
        'code',
        'status',
        'remaining_total',
        'paid_installments'

    ];

    // Define the relationship with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function installments()
    {
        return $this->hasMany(EmployeeAdvanceInstallment::class, 'transaction_id');
    }
    // at top of AdvanceRequest.php
    public static function createInstallments(
        $employeeId,
        $totalAmount,
        $numberOfMonths,
        string|\DateTimeInterface $startMonth,
        $applicationId
    ) {
        if ($numberOfMonths <= 0 || $totalAmount <= 0) return;

        DB::transaction(function () use ($employeeId, $totalAmount, $numberOfMonths, $startMonth, $applicationId) {
            // prevent duplicates for same application
            if (EmployeeAdvanceInstallment::where('application_id', $applicationId)->exists()) {
                return;
            }

            $base = floor(($totalAmount / $numberOfMonths) * 100) / 100;   // 2-dec
            $acc  = round($base * ($numberOfMonths - 1), 2);
            $last = round($totalAmount - $acc, 2);

            $cursor = Carbon::parse($startMonth)->startOfMonth();

            for ($i = 0; $i < $numberOfMonths; $i++) {
                $slice = ($i === $numberOfMonths - 1) ? $last : $base;

                EmployeeAdvanceInstallment::create([
                    'employee_id'        => $employeeId,
                    'application_id'     => $applicationId,
                    'sequence'           => $i + 1, // NEW
                    'installment_amount' => $slice,
                    'due_date'           => (clone $cursor)->endOfMonth()->toDateString(), 
                    'is_paid'            => false,
                    // 'paid_payroll_id' stays null until deduction happens
                ]);

                $cursor->addMonth();
            }
        });
    }


    protected static function booted(): void
    {
        static::creating(function (AdvanceRequest $model) {
            if (empty($model->code)) {
                $model->code = static::nextCode(); // يولد كود فريد
            }
           
            if (is_null($model->remaining_total)) {
                $model->remaining_total = (float) $model->advance_amount;
            }
            if (is_null($model->paid_installments)) {
                $model->paid_installments = 0;
            }
        });
    }

    public static function nextCode(): string
    {
        // مثال: ADV-202508-0001
        $prefix = 'ADV-' . now()->format('Ym') . '-';
        // تحسب العدّاد الحالي لهذا الشهر
        $last = DB::table('hr_advance_requests')
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, LENGTH(?) + 1) AS UNSIGNED)) as max_seq", [$prefix])
            ->value('max_seq');

        $seq = (int) $last + 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    // You can define other relationships or methods as needed
}
